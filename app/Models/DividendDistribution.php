<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DividendDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_manager_id',
        'description',
        'pool_amount',
        'paid_total',
        'paid_count',
        'skipped_count',
        'skipped_breakdown',
        'journal_entry_id',
        'created_by',
    ];

    protected $casts = [
        'skipped_breakdown' => 'array',
    ];

    public function loanManager(): BelongsTo
    {
        return $this->belongsTo(LoanManager::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
