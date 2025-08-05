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
     * Get the user (manager) who recorded the transfer.
     */
    public function loanManager()
    {
        return $this->belongsTo(User::class, 'loan_manager_id');
    }
}