<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'client_id',
        'loan_manager_id',
        'principal_amount',
        'interest_rate',
        'term',
        'repayment_frequency',
        'status',
        'start_date',
    ];

public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the client that owns the loan.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the loan manager (user) who issued the loan.
     */
    public function loanManager()
    {
        return $this->belongsTo(User::class, 'loan_manager_id');
    }
    public function guarantors()
    {
        return $this->hasMany(Guarantor::class);
    }
    public function collaterals()
    {
        return $this->hasMany(Collateral::class);
    }
}