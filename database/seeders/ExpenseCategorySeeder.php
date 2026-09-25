<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $defaultCategories = [
            'Rent' => 'CAT-RENT',
            'Electricity' => 'CAT-ELEC',
            'Water' => 'CAT-WATER',
            'Internet' => 'CAT-INET',
            'Equipment' => 'CAT-EQUIP',
            'Maintenance' => 'CAT-MAINT',
            'Salaries' => 'CAT-SALARY',
            'Cleaning' => 'CAT-CLEAN',
            'Marketing' => 'CAT-MKTG',
            'Other' => 'CAT-OTHER',
        ];

        foreach ($defaultCategories as $name => $code) {
            ExpenseCategory::firstOrCreate(
                ['code' => $code, 'branch_id' => null],
                [
                    'name' => $name,
                    'description' => "Global default category for {$name}",
                    'is_active' => true,
                ]
            );
        }
    }
}
