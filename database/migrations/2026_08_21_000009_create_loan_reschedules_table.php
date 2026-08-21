<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit trail for loan rescheduling: rather than silently overwriting
     * a loan's term/rate/schedule, each reschedule writes one row here
     * capturing the before-and-after so the original terms aren't lost.
     * Scoped to term/rate/frequency/start_date only — changing the
     * principal (a true refinance top-up) isn't handled yet since it
     * would require reconciling against existing payment history, which
     * is a bigger piece of work on its own.
     */
    public function up(): void
    {
        if (!Schema::hasTable('loan_reschedules')) {
            Schema::create('loan_reschedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
                $table->foreignId('loan_manager_id')->constrained('loan_managers')->onDelete('cascade');

                $table->decimal('old_interest_rate', 8, 2);
                $table->integer('old_term');
                $table->string('old_repayment_frequency');
                $table->date('old_start_date');

                $table->decimal('new_interest_rate', 8, 2);
                $table->integer('new_term');
                $table->string('new_repayment_frequency');
                $table->date('new_start_date');

                $table->text('reason')->nullable();
                $table->foreignId('rescheduled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_reschedules');
    }
};
