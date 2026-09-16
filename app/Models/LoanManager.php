<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
        'company_email',
        'company_address',
        'company_logo_path',
        'opening_balance',
        'currency_symbol',
        'support_phone',
        'sms_provider',
        'sms_api_key',
        'sms_api_secret',
        'sms_sender_id',
        'sms_username',
    ];

    // ... (Your user(), clients(), loans(), etc. relationships are all correct) ...
    public function user() { return $this->belongsTo(User::class); }
    /** Cashier/employee accounts under this tenant — users.loan_manager_id stores loan_managers.id, same convention as everywhere else. */
    public function staff() { return $this->hasMany(User::class, 'loan_manager_id', 'id')->where('role', 'cashier'); }
    public function clients() { return $this->hasMany(Client::class, 'loan_manager_id', 'id'); }
    public function loans() { return $this->hasMany(Loan::class, 'loan_manager_id', 'id'); }
    public function clientGroups() { return $this->hasMany(ClientGroup::class, 'loan_manager_id', 'id'); }
    public function branches() { return $this->hasMany(Branch::class, 'loan_manager_id', 'id'); }
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

    /**
     * The company logo as an inline base64 data URI, rather than a
     * public/storage/... URL. Print forms (loan agreement, client
     * statement, savings passbook, thermal receipt) all embed the logo
     * this way instead: it works regardless of whether the storage
     * symlink (php artisan storage:link) exists on a given hosting setup,
     * which has been a recurring source of "images don't show up" issues
     * on shared hosting. Returns null (caller should hide the <img> tag
     * entirely) if there's no logo set or the file can't be read.
     */
    public function logoDataUri(): ?string
    {
        if (empty($this->company_logo_path)) {
            return null;
        }

        try {
            $disk = Storage::disk('public');

            if (!$disk->exists($this->company_logo_path)) {
                return null;
            }

            $mime = $disk->mimeType($this->company_logo_path) ?: 'image/png';
            $contents = $disk->get($this->company_logo_path);

            return 'data:' . $mime . ';base64,' . base64_encode($contents);
        } catch (\Throwable $e) {
            return null;
        }
    }

    // *** THIS IS THE FIX: ***
    // *** THE BROKEN accounts() FUNCTION IS NOW REMOVED. ***
}