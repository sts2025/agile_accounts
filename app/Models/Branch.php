<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_manager_id',
        'name',
        'code',
        'address',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function loanManager(): BelongsTo
    {
        return $this->belongsTo(LoanManager::class, 'loan_manager_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'branch_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'branch_id');
    }
}
