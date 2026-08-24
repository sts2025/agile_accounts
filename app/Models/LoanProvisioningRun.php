<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanProvisioningRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_manager_id',
        'run_date',
        'loan_count',
        'total_outstanding',
        'required_reserve',
        'previous_reserve',
        'delta',
        'breakdown',
        'journal_entry_id',
        'created_by',
    ];

    protected $casts = [
        'run_date' => 'date',
        'breakdown' => 'array',
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
