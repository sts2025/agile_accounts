<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\LoanManager;
use App\Models\Loan;
use App\Models\Guarantor;

class Borrower extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_manager_id',
        'first_name',
        'last_name',
        'nin',
        'phone_number',
        'address',
        'date_of_birth',
        'gender',
        'occupation',
    ];

    // Relationship: A Borrower belongs to a LoanManager
    public function loanManager()
    {
        return $this->belongsTo(LoanManager::class);
    }

    // Relationship: A Borrower has many Loans
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    // Relationship: A Borrower has many Guarantors
    public function guarantors()
    {
        return $this->hasMany(Guarantor::class);
    }
}