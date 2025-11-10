<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\LoanManager\ClientController;
use App\Http\Controllers\LoanManager\LoanController;
use App\Http\Controllers\LoanManager\PaymentController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\LoanManager\ReportController;
use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Controllers\LoanManager\ExpenseController;
use App\Http\Controllers\LoanManager\GuarantorController;
use App\Http\Controllers\LoanManager\CollateralController;
use App\Http\Controllers\LoanManager\BankTransactionController;
use App\Http\Controllers\LoanManager\ProfileController;
use App\Http\Controllers\LoanManager\CashTransactionController;
use App\Models\User;

// Explicitly bind {manager} to the User model
Route::model('manager', User::class);

// --- PUBLIC ROUTES ---
Route::get('/', fn() => redirect()->route('login'));
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [AuthController::class, 'create'])->name('register');
Route::post('/register', [AuthController::class, 'store'])->name('register.store');

// Password reset
Route::get('forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

// --- AUTHENTICATED ROUTES ---
Route::middleware(['auth'])->group(function () {

    // ===================== ADMIN ROUTES =====================
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

        // ✅ Fix: clean and clear route naming
        Route::post('/managers/{manager}/update-settings', [AdminController::class, 'updateSettings'])
            ->name('managers.update');

        // ✅ Add missing routes that were causing 404 errors
        Route::get('/managers/{manager}/activate', [AdminController::class, 'activate'])
            ->name('managers.activate');
        Route::get('/managers/{manager}/suspend', [AdminController::class, 'suspend'])
            ->name('managers.suspend');

        // "Login As" routes
        Route::get('/users/{manager}/impersonate', [AdminController::class, 'impersonate'])
            ->name('users.impersonate');
        Route::get('/users/stop-impersonate', [AdminController::class, 'stopImpersonate'])
            ->name('users.stop_impersonate');
    });

    // ===================== LOAN MANAGER ROUTES =====================
    Route::middleware(CheckSubscriptionStatus::class)->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

        // Clients
        Route::resource('clients', ClientController::class);
        Route::get('/clients/{client}/ledger', [ClientController::class, 'showLedger'])->name('clients.ledger');

        // Loans
Route::get('/loans/calculator', [LoanController::class, 'showCalculator'])->name('loans.showCalculator');
Route::get('/loans/{loan}/download-agreement', [LoanController::class, 'downloadLoanAgreement'])
    ->name('loans.downloadAgreement');
Route::resource('loans', LoanController::class);


        // Payments
        Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'showReceipt'])->name('payments.receipt');

        // Guarantors & Collaterals
        Route::post('/guarantors', [GuarantorController::class, 'store'])->name('guarantors.store');
        Route::post('/collaterals', [CollateralController::class, 'store'])->name('collaterals.store');

        // Bank, Cash, and Expenses
        Route::resource('bank-transactions', BankTransactionController::class)->only(['index', 'store'])->names('bank-transactions');
        Route::resource('expenses', ExpenseController::class)->only(['index', 'store'])->names('expenses');
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
    });
});
