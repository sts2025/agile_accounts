<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\LoanManager\ClientController;
use App\Http\Controllers\LoanManager\LoanController;
use App\Http\Controllers\LoanManager\PaymentController;
use App\Http\Controllers\LoanManager\GuarantorController;
use App\Http\Controllers\LoanManager\CollateralController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\LoanManager\ReportController as LoanManagerReportController;
use App\Http\Controllers\Admin\BroadcastMessageController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- PUBLIC AND GUEST ROUTES ---

// Homepage
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes (for users who are not logged in)
Route::get('/register', [AuthController::class, 'create'])->name('register');
Route::post('/register', [AuthController::class, 'store'])->name('register.store');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ... (keep your existing login/register routes) ...

// --- PASSWORD RESET ROUTES ---
Route::get('forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.update');


// --- PROTECTED LOAN MANAGER ROUTES ---
// For regular, authenticated users (Loan Managers)
Route::middleware(['auth'])->group(function () {
    
    // Loan Manager's Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::resource('clients', ClientController::class);
    Route::resource('loans', LoanController::class);
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('/guarantors', [GuarantorController::class, 'store'])->name('guarantors.store');
    Route::post('/collaterals', [CollateralController::class, 'store'])->name('collaterals.store');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'showReceipt'])->name('payments.receipt');
    Route::get('/dashboard/reports/trial-balance', [LoanManagerReportController::class, 'trialBalance'])->name('manager.reports.trial-balance');
    Route::get('/dashboard/reports/trial-balance/pdf', [LoanManagerReportController::class, 'downloadTrialBalance'])->name('manager.reports.trial-balance.pdf');
    Route::get('/dashboard/reports/profit-and-loss', [LoanManagerReportController::class, 'profitAndLoss'])->name('manager.reports.profit-and-loss');
    Route::get('/dashboard/reports/profit-and-loss/pdf', [LoanManagerReportController::class, 'downloadProfitAndLoss'])->name('manager.reports.profit-and-loss.pdf');
    Route::get('/dashboard/reports/balance-sheet', [LoanManagerReportController::class, 'balanceSheet'])->name('manager.reports.balance-sheet');
    Route::get('/dashboard/reports/balance-sheet/pdf', [LoanManagerReportController::class, 'downloadBalanceSheet'])->name('manager.reports.balance-sheet.pdf');
     // For the manager's Aging Analysis report
    Route::get('/dashboard/reports/aging-analysis', [LoanManagerReportController::class, 'agingAnalysis'])->name('manager.reports.aging-analysis');
    Route::get('/dashboard/reports/aging-analysis/pdf', [LoanManagerReportController::class, 'downloadAgingAnalysis'])->name('manager.reports.aging-analysis.pdf');
// For confirming password to edit a payment
Route::get('/payments/{payment}/edit/confirm', [PaymentController::class, 'showPasswordConfirmationForm'])->name('payments.edit.confirm');
Route::post('/payments/{payment}/edit/confirm', [PaymentController::class, 'confirmPassword'])->name('payments.password.confirm');
// For the Loan Agreement PDF
Route::get('/loans/{loan}/agreement', [LoanController::class, 'downloadLoanAgreement'])->name('loans.agreement.pdf');

});


// --- PROTECTED ADMIN ROUTES ---
// For authenticated users who are also Admins
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::post('/managers/{manager}/activate', [AdminController::class, 'activate'])->name('managers.activate');
    Route::post('/managers/{manager}/suspend', [AdminController::class, 'suspend'])->name('managers.suspend');
    Route::get('/reports/trial-balance', [ReportController::class, 'trialBalance'])->name('reports.trial-balance');
    Route::get('/reports/profit-and-loss', [ReportController::class, 'profitAndLoss'])->name('reports.profit-and-loss');
    Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
// Routes for sending broadcast messages
    Route::get('/broadcast/create', [BroadcastMessageController::class, 'create'])->name('broadcast.create');
    Route::post('/broadcast', [BroadcastMessageController::class, 'store'])->name('broadcast.store');
// Routes for editing and updating a payment
    Route::get('/payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
    Route::put('/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');

});

