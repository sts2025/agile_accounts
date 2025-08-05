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
    Schema::create('cash_transfers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('loan_manager_id')->constrained('users'); // The manager recording the transaction
        $table->date('transaction_date');
        $table->enum('type', ['in', 'out']); // 'in' for receivable, 'out' for sent
        $table->decimal('amount', 15, 2);
        $table->text('description');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_transfers');
    }
};


