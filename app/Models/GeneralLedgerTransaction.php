<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralLedgerTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id',
        'loan_id',
        'transaction_date',
        'description',
        'debit',
        'credit',
    ];

    /**
     * Get the account associated with the transaction.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the loan associated with the transaction.
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}