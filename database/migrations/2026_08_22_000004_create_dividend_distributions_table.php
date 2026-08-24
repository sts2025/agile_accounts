<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit record for each dividend distribution run. Before this table,
     * MfiDividendController::distribute() left no summary trail at all —
     * only the scattered per-member 'dividend' MfiTransaction rows it
     * creates. This gives a single row per distribution event (who ran it,
     * when, how much, how many members were paid vs skipped) and links to
     * the General Journal entry now posted alongside it.
     */
    public function up(): void
    {
        if (!Schema::hasTable('dividend_distributions')) {
            Schema::create('dividend_distributions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_manager_id')->constrained('loan_managers')->onDelete('cascade');

                $table->string('description')->nullable();
                $table->decimal('pool_amount', 15, 2);
                $table->decimal('paid_total', 15, 2);
                $table->unsignedInteger('paid_count');
                $table->unsignedInteger('skipped_count')->default(0);
                $table->json('skipped_breakdown')->nullable();

                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dividend_distributions');
    }
};
