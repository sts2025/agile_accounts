<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * In-app notification log, deliberately named app_notifications (not
     * "notifications") to avoid colliding with Laravel's own Notifiable/
     * DatabaseChannel table convention — User already `use Notifiable`,
     * and we don't want a future `$user->notify(...)` call silently
     * writing into this tenant-scoped table or expecting its schema.
     *
     * One row per recipient per event (fan-out at write time), so "mark as
     * read" is a plain per-row update with no pivot table needed.
     */
    public function up(): void
    {
        if (!Schema::hasTable('app_notifications')) {
            Schema::create('app_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_manager_id')->constrained('loan_managers')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();

                $table->string('type', 40); // loan_approved | loan_disbursed | loan_rejected | loan_written_off | payment_received | loan_paid_off
                $table->string('title', 150);
                $table->string('body', 500)->nullable();
                $table->string('url', 255)->nullable();
                $table->timestamp('read_at')->nullable();

                $table->timestamp('created_at')->nullable();

                $table->index(['user_id', 'read_at']);
                $table->index(['loan_manager_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};
