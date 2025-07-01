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

    // Loan Manager's Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');

    // This single line creates all client routes: index, create, store, etc.
    // This provides the 'clients.index' route that was missing.
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
});