<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    use HasFactory;
    protected $table = 'cash_transfers'; // Assuming your table name is cash_transfers

    // Allow all fields we are trying to save
    protected $fillable = [
        'loan_manager_id', 
        'type', // The key is to make this fillable
        'amount',
        'description',
        'transaction_date',
    ];

    /**
     * Get the loan manager that owns the cash transaction.
     */
    public function loanManager()
    {
        return $this->belongsTo(LoanManager::class, 'loan_manager_id');
    }

    // Since your database is throwing a fatal error on truncation, 
    // we must ensure the validation and saving logic is iron-clad.
    // The previous controller update (saving 'R' or 'P') is the maximum
    // allowed fix within the application code.
}
