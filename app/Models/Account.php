<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // <-- Make sure this is imported

class Account extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'type',
        'description',
    ];

    public function generalLedgerTransactions()
    {
        return $this->hasMany(GeneralLedgerTransaction::class);
    }

    /**
     * THIS IS THE NEW FUNCTION
     * Calculate the opening balance for this account up to a specific date,
     * for a specific loan manager.
     */
    public function getOpeningBalance(Carbon $date, int $managerId)
    {
        // Get the sum of debits and credits *before* the start of the $reportDate
        
        $debits = $this->generalLedgerTransactions()
            ->where('transaction_date', '<', $date->startOfDay())
            // This 'loan' relationship MUST exist on your GeneralLedgerTransaction model
            ->whereHas('loan', function ($query) use ($managerId) {
                $query->where('loan_manager_id', $managerId);
            })
            ->sum('debit');

        $credits = $this->generalLedgerTransactions()
            ->where('transaction_date', '<', $date->startOfDay())
            ->whereHas('loan', function ($query) use ($managerId) {
                $query->where('loan_manager_id', $managerId);
            })
            ->sum('credit');

        // Balance = Debits - Credits (for an asset account like 'Cash on Hand')
        return $debits - $credits;
    }
}