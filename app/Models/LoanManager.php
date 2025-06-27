<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Borrower;
use App\Models\Loan;
use App\Models\SubscriptionPayment;

class LoanManager extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone_number',
        'address',
        'is_active',
        'subscription_ends_at',
    ];

    // Relationship: A LoanManager belongs to a User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship: A LoanManager manages many Borrowers
    public function borrowers()
    {
        return $this->hasMany(Borrower::class);
    }

    // Relationship: A LoanManager has many Loans (indirectly through borrowers, but also directly linked)
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    // Relationship: A LoanManager has many SubscriptionPayments
    public function subscriptionPayments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }
}