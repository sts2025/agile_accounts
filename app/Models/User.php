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
     *
     * A cashier can never be an admin, full stop — they're always someone
     * else's tenant employee. Beyond that, this app has ended up with two
     * legitimate ways an account is recognized as a platform admin (both
     * predate this check and are relied on elsewhere in the codebase):
     *   1. user_type of 'admin' or 'super_admin' (both values exist in the
     *      live users table).
     *   2. User #1 — the original owner/developer account, long treated as
     *      admin by convention (see AuthController::login()'s
     *      `$user->id === 1` special case).
     * Narrowing this to just user_type === 'admin' (as an earlier version
     * of this method did) silently locks out both of those.
     */
    public function isAdmin()
    {
        if ($this->role === 'cashier') {
            return false;
        }

        return $this->id === 1
            || in_array($this->user_type, ['admin', 'super_admin'], true);
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