<?php

namespace App\Services;

use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffService
{
    public function createStaffWithUser(array $data, int $branchId): StaffProfile
    {
        return DB::transaction(function () use ($data, $branchId) {
            $isTrainer = filter_var($data['is_trainer'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // 1. Create Login User Account
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'status' => $data['status'] ?? 'active',
            ]);

            // 2. Attach Role to User
            $roleName = $data['role'] ?? ($isTrainer ? 'trainer' : 'receptionist');
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $user->roles()->attach($role->id);
            }

            // 3. Attach Branch to User
            $user->branches()->syncWithoutDetaching([$branchId]);

            // 4. Generate Staff Code
            $codeService = new StaffCodeService;
            $staffCode = $codeService->generate($branchId, $isTrainer);

            // 5. Create Staff Profile
            return StaffProfile::create([
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'staff_code' => $staffCode,
                'is_trainer' => $isTrainer,
                'designation' => $data['designation'],
                'cnic' => $data['cnic'] ?? null,
                'specialization' => $data['specialization'] ?? null,
                'monthly_salary' => $data['monthly_salary'] ?? 0.00,
                'joining_date' => $data['joining_date'],
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }
}
