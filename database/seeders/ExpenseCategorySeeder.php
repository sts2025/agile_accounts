<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExpenseCategory;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Airtime', 'Breakfast', 'Director\'s Expenses', 'Electricity', 'Allowances',
            'Fuel', 'Loan Recovery', 'Lunch', 'Maintenance of Office Equipments',
            'Office Maintenance', 'Parking & Security', 'Rent', 'Salary & Wages',
            'Stationery & Printing', 'Supper', 'Transport'
        ];

        foreach ($categories as $category) {
            ExpenseCategory::create(['name' => $category]);
        }
    }
}