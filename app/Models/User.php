<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Lab404\Impersonate\Models\Impersonate;
use App\Models\LoanManager;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Impersonate;

    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'role',             // Required for Cashier
        'loan_manager_id',  // Required for Cashier
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Strict Gatekeeper for Admin Panel.
     * This ensures ONLY the Super Admin can pass.
     * * Logic:
     * 1. Must be type 'admin'
     * 2. Cannot be a Cashier (role check)
     * 3. Cannot belong to another company (loan_manager_id check)
     */
    public function isAdmin()
    {
        return $this->user_type === 'admin' 
            && $this->role !== 'cashier'
            && is_null($this->loan_manager_id);
    }
    
    /**
     * Helper to check if user is a Loan Manager (Owner).
     */
    public function isLoanManager()
    {
        return $this->user_type === 'loan_manager' && $this->role !== 'cashier';
    }

    /**
     * Helper to check if user is a Cashier (Employee).
     */
    public function isCashier()
    {
        return $this->role === 'cashier' && !is_null($this->loan_manager_id);
    }
    
    // --- RELATIONSHIPS ---

    public function loanManager(): HasOne
    {
        return $this->hasOne(LoanManager::class);
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(LoanManager::class, 'loan_manager_id');
    }

    // --- CRITICAL HELPER ---
    // This function prevents the "Undefined method" error that causes crashes/redirects.
    public function getCompany()
    {
        if ($this->role === 'cashier') {
            return $this->employer;
        }
        return $this->loanManager;
    }
}