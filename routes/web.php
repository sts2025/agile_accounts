<?php

use Illuminate\Support\Facades\Route;

// --- CONTROLLERS ---
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Models\User;

// Admin Controllers
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BroadcastMessageController;
use App\Http\Controllers\Admin\SubscriptionController;

// Manager Controllers
use App\Http\Controllers\LoanManager\ClientController;
use App\Http\Controllers\LoanManager\ClientGroupController;
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
use App\Http\Controllers\LoanManager\MfiProductController;
use App\Http\Controllers\LoanManager\ChartOfAccountController;
use App\Http\Controllers\LoanManager\JournalEntryController;
use App\Http\Controllers\LoanManager\LoanPenaltyController;
use App\Http\Controllers\LoanManager\LoanPenaltySettingController;
use App\Http\Controllers\LoanManager\ErrorCorrectionController;
use App\Http\Controllers\LoanManager\MfiShareController;
use App\Http\Controllers\LoanManager\MfiDividendController;
use App\Http\Controllers\LoanManager\MfiFixedDepositController;
use App\Http\Controllers\LoanManager\MfiEndOfPeriodController;
use App\Http\Controllers\LoanManager\SavingsController;

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
    // "Return to admin" must stay reachable while impersonating a non-admin
    // user, so it lives outside the ['admin']-gated group below. It does its
    // own check (only restores a session that actually has an
    // 'original_admin_id' stashed by AdminController::impersonate()).
    Route::get('/admin/users/stop-impersonate', [AdminController::class, 'stopImpersonate'])->name('admin.users.stop_impersonate');

    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::post('/subscription/update', [SubscriptionController::class, 'update'])->name('subscription.update');

        // Manager Actions
        Route::put('/managers/{id}/update', [AdminController::class, 'update'])->name('managers.update');
        Route::post('/managers/{id}/activate', [AdminController::class, 'activate'])->name('managers.activate');
        Route::post('/managers/{id}/suspend', [AdminController::class, 'suspend'])->name('managers.suspend');
        Route::delete('/managers/{id}', [AdminController::class, 'destroy'])->name('managers.destroy');

        // MFI Hub is a paid add-on tier, not a self-service feature — only an
        // admin can grant it, after confirming payment outside the app (same
        // manual pattern as the base subscription). $id here is the
        // loan_managers.id, matching MfiUpgradeController's own convention.
        Route::post('/managers/{id}/upgrade-mfi', [MfiUpgradeController::class, 'upgradeToMfi'])->name('managers.upgrade-mfi');

        // "Login As" Route
        Route::get('/users/{id}/impersonate', [AdminController::class, 'impersonate'])->name('users.impersonate');

        // Broadcast Messages: platform-wide announcements shown to loan
        // managers on their dashboard.
        Route::prefix('broadcasts')->name('broadcasts.')->group(function () {
            Route::get('/', [BroadcastMessageController::class, 'index'])->name('index');
            Route::post('/', [BroadcastMessageController::class, 'store'])->name('store');
            Route::post('/{broadcast}/toggle', [BroadcastMessageController::class, 'toggle'])->name('toggle');
            Route::delete('/{broadcast}', [BroadcastMessageController::class, 'destroy'])->name('destroy');
        });
    });

    // ---------------------------------------------------------
    // LOAN MANAGER ROUTES (Protected by 'subscription' Check)
    // ---------------------------------------------------------
    Route::middleware(['subscription'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // --- MFI SAVINGS ROUTES ---
        // Gated behind the 'mfi' middleware: only loan managers who have upgraded
        // (loan_managers.is_mfi = true) can reach these. Non-upgraded managers are
        // bounced back to the dashboard with a message to upgrade first.
        Route::middleware(['mfi'])->prefix('mfi')->name('mfi.')->group(function () {
            Route::get('/savings', [SavingsController::class, 'index'])->name('savings.index');
            Route::get('/savings/create', [SavingsController::class, 'create'])->name('savings.create');
            Route::post('/savings', [SavingsController::class, 'store'])->name('savings.store');
            Route::get('/savings/{id}', [SavingsController::class, 'show'])->name('savings.show');
            Route::get('/savings/{id}/passbook', [SavingsController::class, 'passbook'])->name('savings.passbook');

            // Transaction engine: operates on the real MfiAccount/MfiTransaction
            // tables, respects lien_amount so collateralised savings can't be
            // withdrawn while a loan is active.
            Route::post('/savings/deposit', [SavingsController::class, 'deposit'])->name('savings.deposit');
            Route::post('/savings/withdraw', [SavingsController::class, 'withdraw'])->name('savings.withdraw');
            Route::post('/savings/{id}/hold', [SavingsController::class, 'putOnHold'])->name('savings.hold');
            Route::post('/savings/{id}/unhold', [SavingsController::class, 'takeOffHold'])->name('savings.unhold');
            Route::post('/savings/{id}/close', [SavingsController::class, 'closeAccount'])->name('savings.close');

            // Product Settings: manager-configured loan/savings product rules
            // (collateral ratio, minimum balance, etc.) instead of hardcoded defaults.
            Route::prefix('products')->name('products.')->group(function () {
                Route::get('/', [MfiProductController::class, 'index'])->name('index');
                Route::get('/create', [MfiProductController::class, 'create'])->name('create');
                Route::post('/', [MfiProductController::class, 'store'])->name('store');
                Route::get('/{id}/edit', [MfiProductController::class, 'edit'])->name('edit');
                Route::put('/{id}', [MfiProductController::class, 'update'])->name('update');
                Route::post('/{id}/toggle', [MfiProductController::class, 'toggle'])->name('toggle');
            });

            // Shares: member ownership units, buy/redeem, dividend distribution.
            Route::prefix('shares')->name('shares.')->group(function () {
                Route::get('/', [MfiShareController::class, 'index'])->name('index');
                Route::get('/create', [MfiShareController::class, 'create'])->name('create');
                Route::post('/', [MfiShareController::class, 'store'])->name('store');
                Route::get('/{id}', [MfiShareController::class, 'show'])->name('show');
                Route::post('/{id}/buy', [MfiShareController::class, 'buy'])->name('buy');
                Route::post('/{id}/redeem', [MfiShareController::class, 'redeem'])->name('redeem');
                Route::post('/{id}/close', [MfiShareController::class, 'closeAccount'])->name('close');
            });

            Route::prefix('dividends')->name('dividends.')->group(function () {
                Route::get('/create', [MfiDividendController::class, 'create'])->name('create');
                Route::post('/preview', [MfiDividendController::class, 'preview'])->name('preview');
                Route::post('/distribute', [MfiDividendController::class, 'distribute'])->name('distribute');
            });

            // Fixed Deposits: term savings that mature on a set date.
            Route::prefix('fixed-deposits')->name('fixed-deposits.')->group(function () {
                Route::get('/', [MfiFixedDepositController::class, 'index'])->name('index');
                Route::get('/create', [MfiFixedDepositController::class, 'create'])->name('create');
                Route::post('/', [MfiFixedDepositController::class, 'store'])->name('store');
                Route::get('/{id}', [MfiFixedDepositController::class, 'show'])->name('show');
                Route::post('/{id}/close', [MfiFixedDepositController::class, 'close'])->name('close');
            });

            // End of Period: savings interest application (EOD/EOM job).
            Route::prefix('end-of-period')->name('end-of-period.')->group(function () {
                Route::get('/', [MfiEndOfPeriodController::class, 'index'])->name('index');
                Route::post('/preview', [MfiEndOfPeriodController::class, 'preview'])->name('preview');
                Route::post('/post', [MfiEndOfPeriodController::class, 'post'])->name('post');
            });
        });

        // User Profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        
        // Clients
        Route::post('/clients/check-global', [ClientController::class, 'checkGlobal'])->name('clients.check-global');
        Route::resource('clients', ClientController::class);
        Route::get('/clients/{client}/ledger', [ClientController::class, 'showLedger'])->name('clients.ledger');
        Route::get('/clients/{client}/statement', [ClientController::class, 'statement'])->name('clients.statement');
        Route::get('/clients/{client}/photo', [ClientController::class, 'photo'])->name('clients.photo');
        Route::get('/clients/{client}/id-document', [ClientController::class, 'idDocument'])->name('clients.id-document');
        Route::post('/clients/{client}/blacklist', [ClientController::class, 'blacklist'])->name('clients.blacklist');
        Route::post('/clients/{client}/unblacklist', [ClientController::class, 'unblacklist'])->name('clients.unblacklist');
        Route::post('/clients/{client}/transfer-group', [ClientController::class, 'transferGroup'])->name('clients.transfer-group');
        Route::post('/clients/{client}/convert-type', [ClientController::class, 'convertType'])->name('clients.convert-type');

        // Client Groups (group / joint-liability lending)
        Route::resource('client-groups', ClientGroupController::class);

        // Loans
        Route::get('/loans/calculator', [LoanController::class, 'showCalculator'])->name('loans.showCalculator');
        Route::get('/loans/{loan}/download-agreement', [LoanController::class, 'downloadLoanAgreement'])->name('loans.downloadAgreement');
        Route::patch('/loans/{loan}/status', [LoanController::class, 'updateStatus'])->name('loans.update-status');
        Route::post('/loans/{loan}/approve', [LoanController::class, 'approve'])->name('loans.approve');
        Route::post('/loans/{loan}/reject', [LoanController::class, 'reject'])->name('loans.reject');
        Route::post('/loans/{loan}/disburse', [LoanController::class, 'disburse'])->name('loans.disburse');
        Route::post('/loans/{loan}/reverse-disbursement', [LoanController::class, 'reverseDisbursement'])->name('loans.reverse-disbursement');
        Route::post('/loans/{loan}/write-off', [LoanController::class, 'writeOff'])->name('loans.write-off');
        Route::post('/loans/{loan}/reschedule', [LoanController::class, 'reschedule'])->name('loans.reschedule');
        Route::post('/loans/{loan}/penalties', [LoanPenaltyController::class, 'store'])->name('loans.penalties.store');
        Route::post('/loans/{loan}/penalties/{penalty}/remove', [LoanPenaltyController::class, 'destroy'])->name('loans.penalties.destroy');
        Route::get('/loan-penalty-settings', [LoanPenaltySettingController::class, 'edit'])->name('loan-penalty-settings.edit');
        Route::put('/loan-penalty-settings', [LoanPenaltySettingController::class, 'update'])->name('loan-penalty-settings.update');
        Route::get('/error-correction', [ErrorCorrectionController::class, 'index'])->name('error-correction.index');
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

        // Accounting: Chart of Accounts + General Journal
        Route::prefix('chart-of-accounts')->name('chart-of-accounts.')->group(function () {
            Route::get('/', [ChartOfAccountController::class, 'index'])->name('index');
            Route::get('/create', [ChartOfAccountController::class, 'create'])->name('create');
            Route::post('/', [ChartOfAccountController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [ChartOfAccountController::class, 'edit'])->name('edit');
            Route::put('/{id}', [ChartOfAccountController::class, 'update'])->name('update');
            Route::post('/{id}/toggle', [ChartOfAccountController::class, 'toggle'])->name('toggle');
            Route::post('/seed-defaults', [ChartOfAccountController::class, 'seedDefaults'])->name('seed-defaults');
        });

        Route::prefix('journal-entries')->name('journal-entries.')->group(function () {
            Route::get('/', [JournalEntryController::class, 'index'])->name('index');
            Route::get('/create', [JournalEntryController::class, 'create'])->name('create');
            Route::post('/', [JournalEntryController::class, 'store'])->name('store');
            Route::get('/{id}', [JournalEntryController::class, 'show'])->name('show');
            Route::post('/{id}/reverse', [JournalEntryController::class, 'reverse'])->name('reverse');
        });

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