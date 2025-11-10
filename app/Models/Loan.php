<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// *** REQUIRED IMPORT ***
use App\Models\Client;
// *** REQUIRED IMPORT ***
use App\Models\User;
use App\Models\Payment;
use App\Models\RepaymentSchedule;
use App\Models\Guarantor;
use App\Models\Collateral;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'loan_manager_id',
        'principal_amount',
        'processing_fee',
        'interest_rate',
        'term',
        'repayment_frequency',
        'status',
        'start_date',
    ];

    // *** FIX: Missing client relationship added ***
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    // **********************************************

    public function loanManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loan_manager_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    
    public function repaymentSchedules(): HasMany
    {
        return $this->hasMany(RepaymentSchedule::class, 'loan_id');
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(Guarantor::class);
    }

    public function collaterals(): HasMany
    {
        return $this->hasMany(Collateral::class);
    }

    /**
     * Calculates the total amount that must be repaid (Principal + Interest + Fee).
     */
    public function totalRepayable()
    {
        $totalInterest = $this->principal_amount * ($this->interest_rate / 100);
        return $this->principal_amount + $totalInterest + $this->processing_fee;
    }
    
    /**
     * Calculates the total remaining balance on the loan (Total Repayable - Total Paid).
     */
    public function balance()
    {
        $totalPaid = $this->payments()->sum('amount_paid');
        $totalRepayable = $this->totalRepayable();
        
        // Use max(0, ...) to prevent negative balances (overpayment scenario)
        return max(0, $totalRepayable - $totalPaid);
    }
}