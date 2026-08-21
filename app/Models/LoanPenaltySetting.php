<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPenaltySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_manager_id',
        'penalty_type',
        'penalty_amount',
        'penalty_percent',
        'grace_period_days',
        'provision_rates',
    ];

    protected $casts = [
        'provision_rates' => 'array',
    ];

    public function loanManager(): BelongsTo
    {
        return $this->belongsTo(LoanManager::class);
    }

    /**
     * The provisioning percentage that applies for a loan this many days
     * late, using the largest configured bucket that the days-late figure
     * has reached or exceeded. Returns 0 if nothing configured or the
     * loan isn't late enough for any bucket yet.
     */
    public function provisionRateForDaysLate(int $daysLate): float
    {
        $rates = $this->provision_rates ?? [];
        if (empty($rates)) {
            return 0;
        }

        $applicable = 0;
        foreach ($rates as $bucketDays => $rate) {
            if ($daysLate >= (int) $bucketDays) {
                $applicable = max($applicable, (float) $rate);
            }
        }

        return $applicable;
    }
}
