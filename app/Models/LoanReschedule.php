<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanReschedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'loan_manager_id',
        'old_interest_rate',
        'old_term',
        'old_repayment_frequency',
        'old_start_date',
        'new_interest_rate',
        'new_term',
        'new_repayment_frequency',
        'new_start_date',
        'reason',
        'rescheduled_by',
    ];

    protected $casts = [
        'old_start_date' => 'date',
        'new_start_date' => 'date',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function rescheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rescheduled_by');
    }
}
