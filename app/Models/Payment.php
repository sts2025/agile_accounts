<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Loan;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'payment_date',
        'amount_paid',
        'payment_method',
        'receipt_number',
        'notes',
    ];

    // Cast dates to Carbon instances
    protected $casts = [
        'payment_date' => 'date',
    ];

    // Relationship: A Payment belongs to a Loan
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}