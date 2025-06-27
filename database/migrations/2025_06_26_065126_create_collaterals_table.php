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
        Schema::create('collaterals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans'); // Foreign Key to loans (will be added after loans table)
            $table->string('collateral_type', 100);
            $table->text('description');
            $table->decimal('valuation_amount', 15, 2);
            $table->string('document_details')->nullable();
            $table->boolean('is_released')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collaterals');
    }
};