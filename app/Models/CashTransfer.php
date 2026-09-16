<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_manager_id',
        'transaction_date',
        'type',
        'amount',
        'description',
    ];

    /**
     * The tenant (LoanManager profile) this transfer belongs to.
     * loan_manager_id stores loan_managers.id, same convention as
     * everywhere else — see LoanManager::cashTransfers().
     */
    public function loanManager()
    {
        return $this->belongsTo(LoanManager::class, 'loan_manager_id');
    }
}