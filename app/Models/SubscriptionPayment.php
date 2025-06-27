<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\LoanManager;

class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_manager_id',
        'amount',
        'transaction_id',
        'payment_date',
        'payment_method',
        'status',
        'confirmed_by_admin',
        'notes',
    ];

    // Cast dates to Carbon instances
    protected $casts = [
        'payment_date' => 'date',
        'confirmed_by_admin' => 'boolean', // Cast this to a boolean
    ];

    // Relationship: A SubscriptionPayment belongs to a LoanManager
    public function loanManager()
    {
        return $this->belongsTo(LoanManager::class);
    }
}