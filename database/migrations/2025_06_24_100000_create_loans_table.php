<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();

            // Foreign key to link the loan to the Client who owns it
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            
            // Foreign key to link the loan to the Loan Manager who issued it
            $table->foreignId('loan_manager_id')->constrained('users')->onDelete('cascade');

            $table->decimal('principal_amount', 10, 2); // The initial loan amount
            $table->decimal('interest_rate', 5, 2); // Stored as a percentage, e.g., 10.50 for 10.50%
            $table->integer('term'); // The number of repayment periods (e.g., 12 for 12 months)
            $table->string('repayment_frequency')->default('monthly'); // e.g., 'weekly', 'monthly'

            $table->string('status')->default('pending'); // e.g., 'pending', 'active', 'paid', 'defaulted'
            $table->date('start_date'); // The date the loan becomes active
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};