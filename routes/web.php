<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

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

        // Broadcasts
        Route::get('broadcasts', [BroadcastMessageController::class, 'index'])->name('broadcasts.index');
        Route::post('broadcasts', [BroadcastMessageController::class, 'store'])->name('broadcasts.store');
        Route::patch('broadcasts/{broadcast}/toggle', [BroadcastMessageController::class, 'toggle'])->name('broadcasts.toggle');
        Route::delete('broadcasts/{broadcast}', [BroadcastMessageController::class, 'destroy'])->name('broadcasts.destroy');
    });

    // ---------------------------------------------------------
    // LOAN MANAGER ROUTES (Protected by 'subscription' Check)
    // ---------------------------------------------------------
    // Standard names restored (clients.index, loans.store, etc.)
    Route::middleware(['subscription'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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


// =============================================================
// DATABASE FIXERS & UTILITIES
// =============================================================

Route::get('/fix-database', function() {
    try {
        try { Schema::table('clients', fn($t) => $t->dropForeign('clients_loan_manager_id_foreign')); } catch (\Exception $e) {}
        Schema::table('clients', fn($t) => $t->foreign('loan_manager_id')->references('id')->on('loan_managers')->onDelete('cascade'));

        try { Schema::table('loans', fn($t) => $t->dropForeign('loans_loan_manager_id_foreign')); } catch (\Exception $e) {}
        Schema::table('loans', fn($t) => $t->foreign('loan_manager_id')->references('id')->on('loan_managers')->onDelete('cascade'));

        return "SUCCESS! Database Fixed.";
    } catch (\Exception $e) { return "Error: " . $e->getMessage(); }
});

Route::get('/fix-transaction-tables', function() {
    $results = "";
    $tables = ['bank_transactions', 'cash_transfers', 'expenses'];
    foreach ($tables as $tableName) {
        if (!Schema::hasTable($tableName)) continue;
        try {
            Schema::table($tableName, fn($t) => $t->dropForeign($tableName . '_loan_manager_id_foreign'));
        } catch (\Exception $e) {}
        try {
            Schema::table($tableName, fn($t) => $t->foreign('loan_manager_id')->references('id')->on('loan_managers')->onDelete('cascade'));
            $results .= "$tableName fixed. ";
        } catch (\Exception $e) { $results .= "$tableName error: " . $e->getMessage(); }
    }
    return $results;
});

Route::get('/update-db-v3-cashiers', function () {
    if (!Schema::hasColumn('loan_managers', 'opening_balance')) {
        Schema::table('loan_managers', function (Blueprint $table) {
            $table->decimal('opening_balance', 15, 2)->default(0)->after('company_logo');
        });
    }
    Schema::table('users', function (Blueprint $table) {
        if (!Schema::hasColumn('users', 'role')) {
            $table->string('role')->default('manager')->after('email'); 
        }
        if (!Schema::hasColumn('users', 'loan_manager_id')) {
            $table->unsignedBigInteger('loan_manager_id')->nullable()->after('role');
        }
    });
    return "SUCCESS: Cashier/Balance columns added.";
});

Route::get('/fix-database-columns', function () {
    $tableName = 'loan_managers';
    Schema::table($tableName, function (Blueprint $table) use ($tableName) {
        if (!Schema::hasColumn($tableName, 'company_name')) $table->string('company_name')->nullable();
        if (!Schema::hasColumn($tableName, 'company_address')) $table->string('company_address')->nullable();
        if (!Schema::hasColumn($tableName, 'company_phone')) $table->string('company_phone')->nullable();
        if (!Schema::hasColumn($tableName, 'company_email')) $table->string('company_email')->nullable();
        if (!Schema::hasColumn($tableName, 'company_logo')) $table->string('company_logo')->nullable();
    });
    return 'SUCCESS: Settings columns fixed.';
});