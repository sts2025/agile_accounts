<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    // --- THE FIX: ADDED PRINCIPAL AND INTEREST TO FILLABLE ---
    protected $fillable = [
        'loan_id',
        'payment_date',
        'amount_paid',      // This will now hold the TOTAL (Principal + Interest)
        'principal_paid',   // Split breakdown 1
        'interest_paid',    // Split breakdown 2
        'payment_method',
        'receipt_number',
        'reference_id',
        'notes',
        'collected_by',
    ];

    protected $casts = [
        'payment_date'   => 'date',
        'amount_paid'    => 'decimal:2',
        'principal_paid' => 'decimal:2',
        'interest_paid'  => 'decimal:2',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}