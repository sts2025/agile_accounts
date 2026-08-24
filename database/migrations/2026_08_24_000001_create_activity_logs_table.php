<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lightweight, tenant-scoped audit trail. Rather than instrument every
     * controller action individually, this is populated by a generic model
     * observer (see App\Observers\ActivityLogObserver) registered on the
     * handful of models where "who changed this and when" actually matters
     * for regulatory/compliance purposes (loans, clients, payments, journal
     * entries) — see AppServiceProvider::boot(). Immutable by design: no
     * updated_at, nothing here should ever be edited after the fact.
     */
    public function up(): void
    {
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_manager_id')->nullable()->constrained('loan_managers')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

                $table->string('action', 20); // created | updated | deleted
                $table->string('subject_type', 100); // e.g. "Loan", "Client"
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->string('description', 500)->nullable();
                $table->json('changes')->nullable(); // ['field' => ['old' => ..., 'new' => ...], ...]
                $table->string('ip_address', 45)->nullable();

                $table->timestamp('created_at')->nullable();

                $table->index(['loan_manager_id', 'created_at']);
                $table->index(['subject_type', 'subject_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
