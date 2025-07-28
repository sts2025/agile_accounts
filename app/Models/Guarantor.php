<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guarantor extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'loan_id',
        'first_name',
        'last_name',
        'nin',
        'phone_number',
        'address',
        'occupation',
        'relationship_to_borrower',
    ];

    /**
     * Get the loan that the guarantor belongs to.
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}