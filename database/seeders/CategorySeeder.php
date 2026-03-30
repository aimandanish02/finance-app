<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Housing',     'code' => 'HOUSE',     'color' => '#6366f1', 'is_tax_deductible' => false],
            ['name' => 'Transport',   'code' => 'CAR',       'color' => '#f59e0b', 'is_tax_deductible' => false],
            ['name' => 'Fuel',        'code' => 'GAS',       'color' => '#ef4444', 'is_tax_deductible' => false],
            ['name' => 'Food',        'code' => 'FOOD',      'color' => '#10b981', 'is_tax_deductible' => false],
            ['name' => 'Utilities',   'code' => 'UTILITIES', 'color' => '#3b82f6', 'is_tax_deductible' => true],
            ['name' => 'Healthcare',  'code' => 'HEALTH',    'color' => '#ec4899', 'is_tax_deductible' => true],
            ['name' => 'Education',   'code' => 'EDU',       'color' => '#8b5cf6', 'is_tax_deductible' => true],
            ['name' => 'Business',    'code' => 'BIZ',       'color' => '#14b8a6', 'is_tax_deductible' => true],
            ['name' => 'Other',       'code' => 'OTHER',     'color' => '#94a3b8', 'is_tax_deductible' => false],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['code' => $category['code']],
                array_merge($category, ['is_active' => true])
            );
        }
    }
}