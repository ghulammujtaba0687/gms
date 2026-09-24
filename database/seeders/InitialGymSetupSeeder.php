<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\GymProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialGymSetupSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Gym Profile
        $gym = GymProfile::firstOrCreate([
            'name' => env('INITIAL_GYM_NAME', 'ABC Fitness Gym'),
        ], [
            'phone' => '+923001234567',
            'email' => 'contact@abcfitness.com',
            'whatsapp' => '+923001234567',
            'address' => 'Main Boulevard, Lahore, Pakistan',
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
        ]);

        // 2. Main Branch & Secondary Branch
        $mainBranch = Branch::firstOrCreate([
            'code' => 'DHA-01',
        ], [
            'gym_profile_id' => $gym->id,
            'name' => 'DHA Phase 5 Branch',
            'phone' => '+923001234567',
            'email' => 'dha@abcfitness.com',
            'address' => 'DHA Phase 5, Lahore',
            'is_active' => true,
            'is_main' => true,
        ]);

        $secondBranch = Branch::firstOrCreate([
            'code' => 'GUL-01',
        ], [
            'gym_profile_id' => $gym->id,
            'name' => 'Gulberg Branch',
            'phone' => '+923007654321',
            'email' => 'gulberg@abcfitness.com',
            'address' => 'MM Alam Road, Gulberg, Lahore',
            'is_active' => true,
            'is_main' => false,
        ]);

        // 3. Initial Owner Account
        $ownerEmail = env('INITIAL_OWNER_EMAIL', 'owner@abcfitness.com');
        $ownerPassword = env('INITIAL_OWNER_PASSWORD', 'Password123!');

        $owner = User::firstOrCreate([
            'email' => $ownerEmail,
        ], [
            'name' => env('INITIAL_OWNER_NAME', 'Gym Owner'),
            'phone' => '+923001234567',
            'password' => Hash::make($ownerPassword),
            'status' => 'active',
        ]);

        $ownerRole = Role::where('name', 'owner')->first();
        if ($ownerRole) {
            $owner->roles()->syncWithoutDetaching([$ownerRole->id]);
        }
    }
}
