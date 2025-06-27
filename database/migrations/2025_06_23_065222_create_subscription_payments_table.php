<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_manager_id')->constrained('loan_managers'); // Foreign Key to loan_managers
            $table->decimal('amount', 10, 2);
            $table->string('transaction_id')->nullable(); // Mobile Money transaction ID from user
            $table->date('payment_date'); // Date user claims payment was made
            $table->string('payment_method', 100)->default('Mobile Money');
            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->boolean('confirmed_by_admin')->default(false); // Admin sets to TRUE
            $table->text('notes')->nullable(); // Admin notes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};