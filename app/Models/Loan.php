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
use Carbon\Carbon;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'loan_manager_id',
        'client_group_id',
        'mfi_loan_product_id',
        'principal_amount',
        'processing_fee',
        'interest_rate',
        'term',
        'repayment_frequency',
        'status',
        'start_date',
        'collateral_locked',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_note',
        'disbursement_journal_entry_id',
        'write_off_reason',
        'written_off_by',
        'written_off_at',
        'write_off_journal_entry_id',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'written_off_at' => 'datetime',
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
     * The MFI loan product this loan was created under (Product Settings),
     * if any. Used at repayment time to look up rules like the compulsory
     * savings split percentage.
     */
    public function mfiLoanProduct(): BelongsTo
    {
        return $this->belongsTo(MfiProduct::class, 'mfi_loan_product_id');
    }

    /**
     * The client group this loan was issued to, if it's a group loan.
     * client_id still holds the representative/signer regardless.
     */
    public function clientGroup(): BelongsTo
    {
        return $this->belongsTo(ClientGroup::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function disbursementJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'disbursement_journal_entry_id');
    }

    public function writtenOffBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'written_off_by');
    }

    public function reschedules(): HasMany
    {
        return $this->hasMany(LoanReschedule::class);
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(LoanPenalty::class);
    }

    /**
     * Calculates the total amount that must be repaid (Principal + Interest
     * + Fee + any active/unremoved penalties).
     */
    public function totalRepayable()
    {
        $totalInterest = $this->principal_amount * ($this->interest_rate / 100);
        $activePenalties = $this->penalties()->where('is_removed', false)->sum('amount');
        return $this->principal_amount + $totalInterest + $this->processing_fee + $activePenalties;
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

    /**
     * Principal + interest + processing fee only — the amount the loan's
     * term/frequency schedule is actually amortising. Deliberately excludes
     * one-off penalty charges, which aren't spread over the term and would
     * distort a pro-rata "how much should be paid by now" calculation.
     * Used for arrears aging; totalRepayable()/balance() (which do include
     * penalties) remain the source of truth for what's actually owed.
     */
    public function scheduledRepayable(): float
    {
        $totalInterest = $this->principal_amount * ($this->interest_rate / 100);
        return $this->principal_amount + $totalInterest + ($this->processing_fee ?? 0);
    }

    /**
     * Pro-rata arrears estimate: how far actual payments are behind what
     * should have been paid by today, assuming even repayment across the
     * term. This is an approximation (there's no populated per-installment
     * due-date schedule to compare against — see repaymentSchedules(),
     * which nothing currently writes to) rather than a true days-past-due
     * figure, but it's the same method the loan aging report and loan
     * classification/provisioning both rely on as their single source of
     * truth for "is this loan behind, and by how much."
     */
    public function arrearsAmount(): float
    {
        $totalPaid = $this->payments()->sum('amount_paid');
        $scheduledRepayable = $this->scheduledRepayable();

        $startDate = Carbon::parse($this->start_date);
        $term = max((int) $this->term, 1);
        $freq = strtolower($this->repayment_frequency ?? 'months');

        $endDate = str_contains($freq, 'week')
            ? $startDate->copy()->addWeeks($term)
            : $startDate->copy()->addMonths($term);

        $totalDays = max($startDate->diffInDays($endDate), 1);
        $daysElapsed = $startDate->diffInDays(now(), false);

        if ($daysElapsed <= 0) {
            return 0.0;
        }

        $timeRatio = min($daysElapsed / $totalDays, 1);
        $expectedPayment = $scheduledRepayable * $timeRatio;

        return max(0, round($expectedPayment - $totalPaid, 2));
    }

    /**
     * Days since the loan started, but only counted once it's actually
     * behind (arrearsAmount() > 0) — a loan that's current returns 0
     * regardless of its age. Used to bucket loans into aging/classification
     * tiers (Normal/Watch/Substandard/Doubtful/Loss).
     */
    public function daysInArrears(): int
    {
        if ($this->arrearsAmount() <= 0.01) {
            return 0;
        }

        return max(0, (int) Carbon::parse($this->start_date)->diffInDays(now(), false));
    }
}