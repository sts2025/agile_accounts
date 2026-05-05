<?php

use Illuminate\Support\Facades\Route;

// --- CONTROLLERS ---
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ElevateController; 
use App\Models\User;

// Admin Controllers
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BroadcastMessageController;
use App\Http\Controllers\Admin\SubscriptionController;

// Manager Controllers
use App\Http\Controllers\LoanManager\ClientController;
use App\Http\Controllers\LoanManager\LoanController;
use App\Http\Controllers\LoanManager\PaymentController;
use App\Http\Controllers\LoanManager\ReportController;
use App\Http\Controllers\LoanManager\ExpenseController;
use App\Http\Controllers\LoanManager\GuarantorController;
use App\Http\Controllers\LoanManager\CollateralController;
use App\Http\Controllers\LoanManager\BankTransactionController;
use App\Http\Controllers\LoanManager\ProfileController;
use App\Http\Controllers\LoanManager\CashTransactionController;
use App\Http\Controllers\LoanManager\BusinessSettingsController;
use App\Http\Controllers\LoanManager\StaffController;
use App\Http\Controllers\LoanManager\MfiUpgradeController;
use App\Http\Controllers\LoanManager\SavingsController;
// --- ADDED: The new Transaction Engine Controller ---
use App\Http\Controllers\LoanManager\SavingsTransactionController;

// Explicitly bind {manager} to the User model
Route::model('manager', User::class);

// =============================================================
// PUBLIC ROUTES
// =============================================================
Route::get('/', fn() => redirect()->route('login'));
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [AuthController::class, 'create'])->name('register');
Route::post('/register', [AuthController::class, 'store'])->name('register.store');

// Password Reset
Route::get('forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.update');


// =============================================================
// AUTHENTICATED ROUTES
// =============================================================
Route::middleware(['auth'])->group(function () {

    // ---------------------------------------------------------
    // ADMIN ROUTES
    // ---------------------------------------------------------
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::post('/subscription/update', [SubscriptionController::class, 'update'])->name('subscription.update');

        // Manager Actions
        Route::put('/managers/{id}/update', [AdminController::class, 'update'])->name('managers.update');
        Route::post('/managers/{id}/activate', [AdminController::class, 'activate'])->name('managers.activate');
        Route::post('/managers/{id}/suspend', [AdminController::class, 'suspend'])->name('managers.suspend');
        Route::delete('/managers/{id}', [AdminController::class, 'destroy'])->name('managers.destroy');

        // "Login As" Routes
        Route::get('/users/{id}/impersonate', [AdminController::class, 'impersonate'])->name('users.impersonate');
        Route::get('/users/stop-impersonate', [AdminController::class, 'stopImpersonate'])->name('users.stop_impersonate');
    });

    // ---------------------------------------------------------
    // LOAN MANAGER ROUTES (Protected by 'subscription' Check)
    // ---------------------------------------------------------
    Route::middleware(['subscription'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // --- MFI Upgrade Route ---
        Route::post('/upgrade-to-mfi', [MfiUpgradeController::class, 'upgradeToMfi'])->name('mfi.upgrade');
        
        // --- MFI SAVINGS ROUTES (Updated to use the new Transaction Engine) ---
        // NOTE: Once you register the 'check.microfinance' middleware in Kernel.php, 
        // you can wrap this group like this: Route::middleware(['check.microfinance'])->prefix('mfi')...
        Route::prefix('mfi')->name('mfi.')->group(function () {
            Route::get('/savings', [SavingsController::class, 'index'])->name('savings.index');
            Route::get('/savings/create', [SavingsController::class, 'create'])->name('savings.create');
            Route::post('/savings', [SavingsController::class, 'store'])->name('savings.store');
            Route::get('/savings/{id}', [SavingsController::class, 'show'])->name('savings.show');
            
            // --- NEW: Secure Database Transaction Routes ---
            Route::post('/savings/deposit', [SavingsTransactionController::class, 'deposit'])->name('savings.deposit');
            Route::post('/savings/withdraw', [SavingsTransactionController::class, 'withdraw'])->name('savings.withdraw');
        });

        // User Profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        
        // Elevated Privileges
        Route::post('/manager/elevate/login', [ElevateController::class, 'login'])->name('manager.elevate.login');
        Route::post('/manager/elevate/logout', [ElevateController::class, 'logout'])->name('manager.elevate.logout');

        // Clients
        Route::post('/clients/check-global', [ClientController::class, 'checkGlobal'])->name('clients.check-global');
        Route::resource('clients', ClientController::class);
        Route::get('/clients/{client}/ledger', [ClientController::class, 'showLedger'])->name('clients.ledger');

        // Loans
        Route::get('/loans/calculator', [LoanController::class, 'showCalculator'])->name('loans.showCalculator');
        Route::get('/loans/{loan}/download-agreement', [LoanController::class, 'downloadLoanAgreement'])->name('loans.downloadAgreement');
        Route::patch('/loans/{loan}/status', [LoanController::class, 'updateStatus'])->name('loans.update-status');
        Route::post('/loans/{loan}/approve', [LoanController::class, 'approve'])->name('loans.approve');
        Route::post('/loans/{loan}/reject', [LoanController::class, 'reject'])->name('loans.reject');
        Route::resource('loans', LoanController::class);

        // Payments
        Route::resource('payments', PaymentController::class);
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'showReceipt'])->name('payments.receipt');

        // Guarantors & Collaterals
        Route::post('/guarantors', [GuarantorController::class, 'store'])->name('guarantors.store');
        Route::post('/collaterals', [CollateralController::class, 'store'])->name('collaterals.store');

        // Finances (Bank, Cash, Expenses)
        Route::resource('bank-transactions', BankTransactionController::class)->only(['index', 'store'])->names('bank-transactions');
        Route::resource('expenses', ExpenseController::class)->only(['index', 'store', 'create', 'edit', 'update', 'destroy'])->names('expenses');
        Route::resource('cash-transactions', CashTransactionController::class)->only(['index', 'store'])->names('cash-transactions');

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('daily', [ReportController::class, 'dailyReport'])->name('daily');
            Route::get('daily/pdf', [ReportController::class, 'downloadDailyReport'])->name('daily.pdf');
            Route::get('profit-and-loss', [ReportController::class, 'profitAndLoss'])->name('profit-and-loss');
            Route::get('profit-and-loss/pdf', [ReportController::class, 'downloadProfitAndLoss'])->name('profit-and-loss.pdf');
            Route::get('balance-sheet', [ReportController::class, 'balanceSheet'])->name('balance-sheet');
            Route::get('general-ledger', [ReportController::class, 'generalLedger'])->name('general-ledger');
            Route::get('trial-balance', [ReportController::class, 'trialBalance'])->name('trial-balance');
            Route::get('loan-aging', [ReportController::class, 'loanAging'])->name('loan-aging');
            Route::get('print-forms', [ReportController::class, 'showPrintForms'])->name('print-forms');
        });

        // Settings & Staff
        Route::prefix('manager')->name('manager.')->group(function () {
            Route::get('/settings', [BusinessSettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [BusinessSettingsController::class, 'update'])->name('settings.update');
            
            Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
            Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
            Route::delete('/staff/{id}', [StaffController::class, 'destroy'])->name('staff.destroy');
        });
    });
});