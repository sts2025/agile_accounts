<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Account; // <-- THIS IS THE MISSING LINE THAT FIXES THE ERROR

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // Assets (Financial Assets, as you specified)
            ['name' => 'Cash on Hand', 'type' => 'Asset'],
            ['name' => 'Loans Receivable', 'type' => 'Asset'],
            ['name' => 'Interest Receivable', 'type' => 'Asset'],

            // Liabilities (Money the business owes)
            // We can add more later if needed
            
            // Equity
            ['name' => 'Owner\'s Equity', 'type' => 'Equity'],

            // Income
            ['name' => 'Interest Income', 'type' => 'Income'],

            // Expenses
            ['name' => 'Bank Fees', 'type' => 'Expense'],
        ];

        foreach ($accounts as $account) {
            // This ensures we don't create duplicate accounts if we run the seeder again
            Account::firstOrCreate(['name' => $account['name']], $account);
        }
    }
}