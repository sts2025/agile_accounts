<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPenalty extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'loan_manager_id',
        'amount',
        'reason',
        'applied_by',
        'is_removed',
        'removed_by',
        'removed_at',
        'removal_reason',
    ];

    protected $casts = [
        'is_removed' => 'boolean',
        'removed_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function removedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by');
    }
}
