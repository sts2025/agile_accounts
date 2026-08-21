<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_manager_id',
        'code',
        'name',
        'type',
        'description',
        'bank_name',
        'external_account_number',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function loanManager(): BelongsTo
    {
        return $this->belongsTo(LoanManager::class);
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Assets and expenses increase with a debit; liabilities, equity, and
     * income increase with a credit. Used for display (which column an
     * account's balance should read as "normal") rather than for storage.
     */
    public function getNormalBalanceAttribute(): string
    {
        return in_array($this->type, ['asset', 'expense'], true) ? 'debit' : 'credit';
    }

    /**
     * Net balance as of an optional cut-off date, expressed in the
     * account's normal-balance direction (so an asset account with more
     * debits than credits returns a positive number).
     */
    public function balanceAsOf(?string $date = null): float
    {
        $query = $this->journalEntryLines();

        if ($date) {
            $query->whereHas('journalEntry', function ($q) use ($date) {
                $q->where('entry_date', '<=', $date);
            });
        }

        $debits = (clone $query)->sum('debit');
        $credits = (clone $query)->sum('credit');

        return $this->normal_balance === 'debit'
            ? (float) $debits - (float) $credits
            : (float) $credits - (float) $debits;
    }
}
