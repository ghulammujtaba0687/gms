<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'owner' => 'Gym Owner',
            'manager' => 'Branch Manager',
            'receptionist' => 'Receptionist',
            'trainer' => 'Trainer',
        ];

        foreach ($roles as $name => $displayName) {
            Role::firstOrCreate(['name' => $name], ['display_name' => $displayName]);
        }

        $permissions = [
            'branches.view' => 'View Branches',
            'branches.manage' => 'Manage Branches',
            'staff.view' => 'View Staff',
            'staff.manage' => 'Manage Staff',
            'members.view' => 'View Members',
            'members.manage' => 'Manage Members',
            'membership_plans.manage' => 'Manage Membership Plans',
            'memberships.manage' => 'Manage Memberships',
            'payments.manage' => 'Manage Payments',
            'attendance.manage' => 'Manage Attendance',
            'expenses.manage' => 'Manage Expenses',
            'reports.view' => 'View Reports',
            'settings.manage' => 'Manage Settings',
            'audit_logs.view' => 'View Audit Logs',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::firstOrCreate(['name' => $name], ['display_name' => $displayName]);
        }

        // Assign permissions to roles
        $ownerRole = Role::where('name', 'owner')->first();
        $ownerRole->permissions()->sync(Permission::all());

        $managerRole = Role::where('name', 'manager')->first();
        $managerRole->permissions()->sync(
            Permission::whereIn('name', [
                'branches.view', 'staff.view', 'members.view', 'members.manage',
                'membership_plans.manage', 'memberships.manage', 'payments.manage',
                'attendance.manage', 'expenses.manage', 'reports.view',
            ])->get()
        );

        $receptionistRole = Role::where('name', 'receptionist')->first();
        $receptionistRole->permissions()->sync(
            Permission::whereIn('name', [
                'members.view', 'members.manage', 'memberships.manage',
                'payments.manage', 'attendance.manage',
            ])->get()
        );

        $trainerRole = Role::where('name', 'trainer')->first();
        $trainerRole->permissions()->sync(
            Permission::whereIn('name', [
                'members.view', 'attendance.manage',
            ])->get()
        );
    }
}
