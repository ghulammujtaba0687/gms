<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipFreeze;
use App\Models\Payment;
use App\Models\SystemNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function createNotification(array $data): ?SystemNotification
    {
        try {
            return DB::transaction(function () use ($data) {
                return SystemNotification::create([
                    'branch_id' => $data['branch_id'],
                    'user_id' => $data['user_id'] ?? null,
                    'type' => $data['type'],
                    'title' => $data['title'],
                    'message' => $data['message'],
                    'idempotency_key' => $data['idempotency_key'],
                    'notifiable_type' => $data['notifiable_type'] ?? null,
                    'notifiable_id' => $data['notifiable_id'] ?? null,
                ]);
            });
        } catch (QueryException $e) {
            // Return existing notification on duplicate idempotency key
            if (str_contains($e->getMessage(), 'idempotency_key') || $e->getCode() == '23000') {
                return SystemNotification::where('idempotency_key', $data['idempotency_key'])->first();
            }
            throw $e;
        }
    }

    public function getUnreadCount(User $user, ?int $branchId = null): int
    {
        $query = SystemNotification::unread()->forUser($user);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif (! $user->hasRole('owner')) {
            $query->where('branch_id', session('active_branch_id'));
        }

        return $query->count();
    }

    public function getNotifications(User $user, ?int $branchId = null, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SystemNotification::forUser($user)->latest();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif (! $user->hasRole('owner')) {
            $query->where('branch_id', session('active_branch_id'));
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'unread') {
                $query->unread();
            } elseif ($filters['status'] === 'read') {
                $query->whereNotNull('read_at');
            }
        }

        return $query->paginate($perPage);
    }

    public function markAsRead(SystemNotification $notification): bool
    {
        return $notification->update(['read_at' => now()]);
    }

    public function markAllAsRead(User $user, ?int $branchId = null): int
    {
        $query = SystemNotification::unread()->forUser($user);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif (! $user->hasRole('owner')) {
            $query->where('branch_id', session('active_branch_id'));
        }

        return $query->update(['read_at' => now()]);
    }

    public function processExpiryReminders(): int
    {
        $count = 0;
        $today = Carbon::today();

        // Check each active branch
        $branches = DB::table('branches')->where('is_active', true)->pluck('id');

        foreach ($branches as $branchId) {
            $enabled = SettingService::get('enable_expiry_notifications', true, $branchId);
            if (! $enabled) {
                continue;
            }

            $rawDays = SettingService::get('membership_expiry_reminder_days', '7,3,1', $branchId);
            $reminderDays = array_map('intval', explode(',', $rawDays));

            foreach ($reminderDays as $days) {
                if ($days <= 0) {
                    continue;
                }

                $targetDate = $today->copy()->addDays($days)->toDateString();

                $expiringMemberships = Membership::withoutGlobalScope('branch_scope')
                    ->where('branch_id', $branchId)
                    ->where('status', 'active')
                    ->whereDate('end_date', $targetDate)
                    ->with('member')
                    ->get();

                foreach ($expiringMemberships as $membership) {
                    $memberName = $membership->member->full_name ?? 'Member';
                    $idempotencyKey = "expiry_{$days}d_ms_{$membership->id}_{$targetDate}";

                    $created = $this->createNotification([
                        'branch_id' => $branchId,
                        'type' => 'expiry_reminder',
                        'title' => "Membership Expiring in {$days} Day(s)",
                        'message' => "Membership for {$memberName} ({$membership->plan_name_snapshot}) expires on {$targetDate}.",
                        'idempotency_key' => $idempotencyKey,
                        'notifiable_type' => Member::class,
                        'notifiable_id' => $membership->member_id,
                    ]);

                    if ($created && $created->wasRecentlyCreated) {
                        $count++;
                    }
                }
            }

            // Expired today notifications
            $expiredMemberships = Membership::withoutGlobalScope('branch_scope')
                ->where('branch_id', $branchId)
                ->where('status', 'expired')
                ->whereDate('end_date', $today->toDateString())
                ->with('member')
                ->get();

            foreach ($expiredMemberships as $membership) {
                $memberName = $membership->member->full_name ?? 'Member';
                $idempotencyKey = "expired_ms_{$membership->id}_{$today->toDateString()}";

                $created = $this->createNotification([
                    'branch_id' => $branchId,
                    'type' => 'expired',
                    'title' => 'Membership Expired Today',
                    'message' => "Membership for {$memberName} ({$membership->plan_name_snapshot}) has expired.",
                    'idempotency_key' => $idempotencyKey,
                    'notifiable_type' => Member::class,
                    'notifiable_id' => $membership->member_id,
                ]);

                if ($created && $created->wasRecentlyCreated) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function processDuesReminders(): int
    {
        $count = 0;
        $currentMonth = Carbon::now()->format('Y-m');

        $branches = DB::table('branches')->where('is_active', true)->pluck('id');

        foreach ($branches as $branchId) {
            $enabled = SettingService::get('enable_dues_notifications', true, $branchId);
            if (! $enabled) {
                continue;
            }

            $paymentsWithDues = Payment::withoutGlobalScope('branch_scope')
                ->where('branch_id', $branchId)
                ->where('remaining_balance', '>', 0)
                ->with('member')
                ->get();

            foreach ($paymentsWithDues as $payment) {
                $member = $payment->member;
                if (! $member) {
                    continue;
                }

                $idempotencyKey = "dues_pay_{$payment->id}_{$currentMonth}";

                $created = $this->createNotification([
                    'branch_id' => $branchId,
                    'type' => 'dues_reminder',
                    'title' => 'Outstanding Payment Dues',
                    'message' => "Member {$member->full_name} ({$member->member_code}) has an outstanding balance of PKR ".number_format($payment->remaining_balance, 2)." on payment {$payment->payment_code}.",
                    'idempotency_key' => $idempotencyKey,
                    'notifiable_type' => Member::class,
                    'notifiable_id' => $member->id,
                ]);

                if ($created && $created->wasRecentlyCreated) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function processFreezeReminders(): int
    {
        $count = 0;
        $tomorrow = Carbon::tomorrow()->toDateString();

        $branches = DB::table('branches')->where('is_active', true)->pluck('id');

        foreach ($branches as $branchId) {
            $enabled = SettingService::get('enable_freeze_notifications', true, $branchId);
            if (! $enabled) {
                continue;
            }

            $endingFreezes = MembershipFreeze::withoutGlobalScope('branch_scope')
                ->where('branch_id', $branchId)
                ->where('status', 'approved')
                ->whereDate('freeze_end_date', $tomorrow)
                ->with('member')
                ->get();

            foreach ($endingFreezes as $freeze) {
                $memberName = $freeze->member->full_name ?? 'Member';
                $idempotencyKey = "freeze_ending_fz_{$freeze->id}_{$tomorrow}";

                $created = $this->createNotification([
                    'branch_id' => $branchId,
                    'type' => 'freeze_ending',
                    'title' => 'Membership Freeze Ending Tomorrow',
                    'message' => "Membership freeze for {$memberName} ends on {$tomorrow}. Subscription will resume automatically.",
                    'idempotency_key' => $idempotencyKey,
                    'notifiable_type' => Member::class,
                    'notifiable_id' => $freeze->member_id,
                ]);

                if ($created && $created->wasRecentlyCreated) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function cleanUpReadNotifications(int $days = 60): int
    {
        $cutoff = Carbon::now()->subDays($days);

        return SystemNotification::whereNotNull('read_at')
            ->where('read_at', '<=', $cutoff)
            ->delete();
    }
}
