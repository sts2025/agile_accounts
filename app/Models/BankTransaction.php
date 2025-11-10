<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * *** THIS IS THE FIX: We must add all the fields here. ***
     */
    protected $fillable = [
        'loan_manager_id',
        'type',
        'amount',
        'description',
        'deposit_date', // Make sure this matches your DB column
    ];

    /**
     * Get the loan manager that owns the bank transaction.
     */
    public function loanManager()
    {
        return $this->belongsTo(LoanManager::class);
    }
}