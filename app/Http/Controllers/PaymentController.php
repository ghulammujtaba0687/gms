<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\RefundPaymentRequest;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Models\GymProfile;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use App\Services\PaymentCodeService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $query = Payment::with(['member', 'membership', 'branch', 'recorder']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        if ($request->boolean('dues_only')) {
            $query->where('remaining_balance', '>', 0);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_code', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($mq) use ($search) {
                        $mq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('member_code', 'like', "%{$search}%");
                    });
            });
        }

        $payments = $query->latest()->paginate(15)->withQueryString();

        return view('payments.index', compact('payments'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Payment::class);

        $user = auth()->user();
        $selectedMember = null;
        $selectedMembership = null;

        if ($request->filled('member_id')) {
            $selectedMember = Member::find($request->member_id);
            if ($selectedMember && ! $user->hasBranchAccess($selectedMember->branch_id)) {
                abort(403, 'Unauthorized member branch selection.');
            }
        }

        if ($request->filled('membership_id')) {
            $selectedMembership = Membership::find($request->membership_id);
            if ($selectedMembership) {
                $selectedMember = $selectedMembership->member;
            }
        }

        return view('payments.create', compact('selectedMember', 'selectedMembership'));
    }

    public function store(StorePaymentRequest $request, PaymentCodeService $codeService): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $user = auth()->user();
        $member = Member::findOrFail($request->member_id);

        if (! $user->hasBranchAccess($member->branch_id)) {
            abort(403, 'Unauthorized cross-branch payment recording.');
        }

        $amountDue = (float) $request->amount_due;
        $discountAmount = (float) ($request->discount_amount ?? 0.00);
        $amountPaid = (float) $request->amount_paid;

        $netDue = max(0, $amountDue - $discountAmount);
        $remainingBalance = max(0, $netDue - $amountPaid);

        $status = 'paid';
        if ($remainingBalance > 0) {
            $status = 'partial';
        }
        if (in_array($request->payment_method, ['bank_transfer', 'easypaisa', 'jazzcash']) && $request->hasFile('proof')) {
            $status = 'pending_verification';
        }

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $file = $request->file('proof');
            $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
            $proofPath = $file->storeAs("payments/proofs/{$member->branch_id}", $filename, 'public');
        }

        $paymentCode = $codeService->generatePaymentCode($member->branch_id);

        $payment = Payment::create([
            'branch_id' => $member->branch_id,
            'member_id' => $member->id,
            'membership_id' => $request->membership_id,
            'payment_code' => $paymentCode,
            'amount_due' => $amountDue,
            'amount_paid' => $amountPaid,
            'discount_amount' => $discountAmount,
            'discount_reason' => $request->discount_reason,
            'remaining_balance' => $remainingBalance,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'proof_path' => $proofPath,
            'payment_date' => $request->payment_date,
            'status' => $status,
            'notes' => $request->notes,
            'recorded_by' => $user->id,
        ]);

        AuditLogService::log(
            'Financial Payments',
            'Recorded Payment: '.$payment->payment_code.' (PKR '.$amountPaid.') for Member: '.$member->full_name,
            null,
            $payment->toArray()
        );

        // System Notification on Payment Confirmation (Phase 13)
        if (SettingService::get('enable_payment_notifications', true, $member->branch_id)) {
            app(NotificationService::class)->createNotification([
                'branch_id' => $member->branch_id,
                'type' => 'payment_received',
                'title' => 'Payment Received: '.$payment->payment_code,
                'message' => 'Payment of PKR '.number_format($amountPaid, 2)." received for member {$member->full_name}. Remaining balance: PKR ".number_format($remainingBalance, 2).'.',
                'idempotency_key' => "payment_rec_{$payment->id}",
                'notifiable_type' => Member::class,
                'notifiable_id' => $member->id,
            ]);
        }

        return redirect()->route('payments.show', $payment->id)->with('success', 'Payment recorded successfully with Code: '.$paymentCode);
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        return view('payments.show', compact('payment'));
    }

    public function verify(Payment $payment): RedirectResponse
    {
        $this->authorize('verify', $payment);

        $oldData = $payment->toArray();

        $status = $payment->remaining_balance > 0 ? 'partial' : 'paid';

        $payment->update([
            'status' => $status,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        AuditLogService::log(
            'Financial Payments',
            'Verified Payment Proof for Code: '.$payment->payment_code,
            $oldData,
            $payment->toArray()
        );

        return redirect()->back()->with('success', 'Payment proof verified successfully.');
    }

    public function refund(RefundPaymentRequest $request, Payment $payment, PaymentCodeService $codeService): RedirectResponse
    {
        $this->authorize('refund', $payment);

        $oldData = $payment->toArray();
        $refundAmount = (float) $request->refund_amount;

        $refundCode = $codeService->generateRefundCode($payment->branch_id);

        $refund = Refund::create([
            'branch_id' => $payment->branch_id,
            'payment_id' => $payment->id,
            'member_id' => $payment->member_id,
            'refund_code' => $refundCode,
            'refund_amount' => $refundAmount,
            'refund_reason' => $request->refund_reason,
            'refund_method' => $request->refund_method,
            'refund_date' => now()->format('Y-m-d'),
            'approved_by' => auth()->id(),
        ]);

        $payment->update([
            'status' => 'refunded',
        ]);

        AuditLogService::log(
            'Financial Payments',
            'Issued Refund: '.$refundCode.' (PKR '.$refundAmount.') for Payment Code: '.$payment->payment_code,
            $oldData,
            $refund->toArray()
        );

        return redirect()->back()->with('success', 'Refund processed successfully with Code: '.$refundCode);
    }

    public function receipt(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $gymProfile = GymProfile::first();

        return view('payments.receipt', compact('payment', 'gymProfile'));
    }
}
