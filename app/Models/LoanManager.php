<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
// *** NOTE: The 'HasMany' and 'Account' imports for the broken function are gone. ***

class LoanManager extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone_number',
        'address',
        'is_active',
        'subscription_ends_at',
        'company_name',
        'company_phone',
        'company_logo_path',
        'currency_symbol', 
        'support_phone',
    ];

    // ... (Your user(), clients(), loans(), etc. relationships are all correct) ...
    public function user() { return $this->belongsTo(User::class); }
    /** Cashier/employee accounts under this tenant — users.loan_manager_id stores loan_managers.id, same convention as everywhere else. */
    public function staff() { return $this->hasMany(User::class, 'loan_manager_id', 'id')->where('role', 'cashier'); }
    public function clients() { return $this->hasMany(Client::class, 'loan_manager_id', 'id'); }
    public function loans() { return $this->hasMany(Loan::class, 'loan_manager_id', 'id'); }
    public function clientGroups() { return $this->hasMany(ClientGroup::class, 'loan_manager_id', 'id'); }
    public function chartOfAccounts() { return $this->hasMany(ChartOfAccount::class, 'loan_manager_id', 'id'); }
    public function journalEntries() { return $this->hasMany(JournalEntry::class, 'loan_manager_id', 'id'); }
    public function payments() { return $this->hasManyThrough(Payment::class, Loan::class); }
    public function expenses() { return $this->hasMany(Expense::class, 'loan_manager_id', 'id'); }
    public function bankTransactions() { return $this->hasMany(BankTransaction::class, 'loan_manager_id', 'id'); }
    public function cashTransactions() { return $this->hasMany(CashTransaction::class, 'loan_manager_id', 'id'); }


    // --- GLOBAL HELPER METHODS ---
    public static function getCurrency()
    {
        if (Auth::check() && Auth::user()->loanManager) {
            return Auth::user()->loanManager->currency_symbol ?? 'UGX';
        }
        return 'UGX';
    }

    public static function getGlobalSupportPhone()
    {
        return '0740859082'; // Default
    }

    // *** THIS IS THE FIX: ***
    // *** THE BROKEN accounts() FUNCTION IS NOW REMOVED. ***
}