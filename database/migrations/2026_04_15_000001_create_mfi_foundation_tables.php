<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. ADD MFI FLAG TO LOAN MANAGERS
        Schema::table('loan_managers', function (Blueprint $table) {
            if (!Schema::hasColumn('loan_managers', 'is_mfi')) {
               $table->boolean('is_mfi')->default(false);
            }
        });

        // 2. ENSURE CLIENTS TABLE HAS REQUIRED MFI KYC FIELDS
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'national_id')) {
                $table->string('national_id')->nullable()->after('phone_number');
            }
            if (!Schema::hasColumn('clients', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('national_id');
            }
            if (!Schema::hasColumn('clients', 'business_occupation')) {
                $table->string('business_occupation')->nullable()->after('address');
            }
        });

        // 3. MFI PRODUCTS TABLE (Defines rules for Loans, Savings, etc.)
        Schema::create('mfi_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_manager_id')->constrained('users')->onDelete('cascade');
            $table->string('name'); // e.g., "Daily Savings", "Standard Loan"
            $table->string('product_type'); // 'loan', 'savings', 'shares'
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->json('rules')->nullable(); // JSON to store penalties, minimum balances, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. MFI ACCOUNTS TABLE (Replaces the basic 'loans' table for upgraded users)
        Schema::create('mfi_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_manager_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('mfi_product_id')->constrained('mfi_products')->onDelete('cascade');
            
            $table->string('account_number')->unique();
            $table->string('account_type'); // 'loan', 'savings'
            
            // For Loans
            $table->decimal('principal_amount', 15, 2)->default(0); 
            $table->integer('term')->nullable(); 
            
            // For both Savings and Loans
            $table->decimal('balance', 15, 2)->default(0); 
            $table->string('status')->default('active'); // active, paid, dormant, defaulted
            
            $table->timestamps();
        });

        // 5. MFI TRANSACTIONS TABLE (The Central Nervous System)
        Schema::create('mfi_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_manager_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('mfi_account_id')->constrained('mfi_accounts')->onDelete('cascade');
            
            $table->string('transaction_type'); // deposit, withdrawal, loan_repayment, penalty, fee
            $table->decimal('amount', 15, 2);
            $table->decimal('debit', 15, 2)->default(0); // Money going OUT of MFI (Withdrawals, Loan Disbursement)
            $table->decimal('credit', 15, 2)->default(0); // Money coming INTO MFI (Deposits, Loan Repayments)
            
            $table->date('transaction_date');
            $table->string('payment_method')->default('Cash');
            $table->string('reference_number')->nullable();
            $table->text('narration')->nullable(); // "Loan repayment for Jan", "Weekly savings"
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mfi_transactions');
        Schema::dropIfExists('mfi_accounts');
        Schema::dropIfExists('mfi_products');
        
        Schema::table('loan_managers', function (Blueprint $table) {
            $table->dropColumn('is_mfi');
        });
    }
};