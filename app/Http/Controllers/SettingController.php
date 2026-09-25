<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setting\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\AuditLogService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Setting::class);

        $user = auth()->user();
        $activeBranchId = session('active_branch_id');

        $settings = [
            'gym_name' => SettingService::get('gym_name', 'GMS Gym System', $activeBranchId),
            'currency_symbol' => SettingService::get('currency_symbol', 'PKR', $activeBranchId),
            'receipt_footer_terms' => SettingService::get('receipt_footer_terms', 'Thank you for training with us!', $activeBranchId),
            'freeze_max_days' => SettingService::get('freeze_max_days', 30, $activeBranchId),
            'gym_logo' => SettingService::get('gym_logo', null, $activeBranchId),
        ];

        return view('settings.index', compact('user', 'activeBranchId', 'settings'));
    }

    public function store(UpdateSettingsRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $activeBranchId = session('active_branch_id');

        // Only owner can update Global Settings (when activeBranchId is null)
        if (! $user->hasRole('owner') && ! $activeBranchId) {
            abort(403, 'Only Gym Owner can update Global Settings.');
        }

        $targetBranchId = $user->hasRole('owner') ? $activeBranchId : $activeBranchId;

        $fields = [
            'gym_name' => 'general',
            'gym_phone' => 'general',
            'gym_email' => 'general',
            'gym_address' => 'general',
            'receipt_footer' => 'branding',
            'receipt_footer_terms' => 'branding',
            'currency_symbol' => 'general',
            'freeze_max_days' => 'operational',
            'expiry_reminder_days' => 'operational',
        ];

        foreach ($fields as $key => $group) {
            if ($request->has($key)) {
                $oldVal = SettingService::get($key, null, $targetBranchId);
                $newVal = $request->input($key);

                SettingService::set($key, $newVal, $targetBranchId, $group);

                AuditLogService::log(
                    'Application Settings',
                    "Updated Setting: {$key}",
                    ['key' => $key, 'value' => $oldVal],
                    ['key' => $key, 'value' => $newVal]
                );
            }
        }

        // Logo upload handling
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
            $logoPath = $file->storeAs('branding/logos', $filename, 'public');

            SettingService::set('gym_logo', $logoPath, $targetBranchId, 'branding');

            AuditLogService::log('Application Settings', 'Updated Gym Logo Branding');
        }

        return redirect()->back()->with('success', 'Application settings and branding updated successfully.');
    }
}
