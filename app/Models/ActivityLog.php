<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // immutable — only created_at is meaningful

    protected $fillable = [
        'loan_manager_id',
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'changes',
        'ip_address',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public function loanManager(): BelongsTo
    {
        return $this->belongsTo(LoanManager::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
