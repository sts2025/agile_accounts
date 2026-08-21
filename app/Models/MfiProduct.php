<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MfiProduct extends Model
{
    use HasFactory;

    protected $table = 'mfi_products';

    protected $guarded = [];

    protected $casts = [
        'rules' => 'array',
        'is_active' => 'boolean',
        'interest_rate' => 'decimal:2',
    ];

    public function accounts()
    {
        return $this->hasMany(MfiAccount::class, 'mfi_product_id');
    }

    public function scopeLoanProducts($query)
    {
        return $query->where('product_type', 'loan');
    }

    public function scopeSavingsProducts($query)
    {
        return $query->where('product_type', 'savings');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // --- Loan product rules (read from the JSON `rules` column) ---

    public function getCollateralRatioAttribute(): float
    {
        return (float) ($this->rules['collateral_ratio'] ?? 0.30);
    }

    public function getRequiresGuarantorAttribute(): bool
    {
        return (bool) ($this->rules['requires_guarantor'] ?? false);
    }

    /**
     * Percentage of every cash repayment on this loan product that is
     * automatically diverted into the client's savings account as a
     * compulsory top-up (in addition to the loan repayment itself).
     * 0 means no auto-split.
     */
    public function getCompulsorySavingsPercentAttribute(): float
    {
        return (float) ($this->rules['compulsory_savings_percent'] ?? 0);
    }

    // --- Savings product rules ---

    public function getMinimumBalanceAttribute(): float
    {
        return (float) ($this->rules['minimum_balance'] ?? 0);
    }

    public function getIsCompulsoryAttribute(): bool
    {
        return (bool) ($this->rules['is_compulsory'] ?? false);
    }

    public function getAllowWithdrawalsAttribute(): bool
    {
        return (bool) ($this->rules['allow_withdrawals'] ?? true);
    }

    // --- Shares product rules ---

    public function getShareValueAttribute(): float
    {
        return (float) ($this->rules['share_value'] ?? 1000);
    }

    public function scopeShareProducts($query)
    {
        return $query->where('product_type', 'shares');
    }

    // --- Fixed Deposit product rules ---

    public function getTermMonthsAttribute(): int
    {
        return (int) ($this->rules['term_months'] ?? 12);
    }

    public function getEarlyWithdrawalPenaltyPercentAttribute(): float
    {
        return (float) ($this->rules['early_withdrawal_penalty_percent'] ?? 10);
    }

    public function scopeFixedDepositProducts($query)
    {
        return $query->where('product_type', 'fixed_deposit');
    }
}
