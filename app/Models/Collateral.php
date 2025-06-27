<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collateral extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'loan_id',
        'collateral_type',
        'description',
        'valuation_amount',
        'document_details',
        'is_released',
    ];

    /**
     * Get the loan that the collateral is for.
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}