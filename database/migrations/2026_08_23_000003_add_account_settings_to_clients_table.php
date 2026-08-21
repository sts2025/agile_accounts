<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Minimal "customer account settings": which staff member (owner or
     * cashier) is this client's assigned officer, and how do they prefer to
     * be notified. No branch/statement-frequency concept exists elsewhere
     * in the app to hang more onto, so kept deliberately small.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'assigned_user_id')) {
                $table->foreignId('assigned_user_id')->nullable()->after('loan_manager_id')
                    ->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('clients', 'preferred_notification_channel')) {
                $table->string('preferred_notification_channel')->nullable()->default('sms')->after('assigned_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['assigned_user_id']);
            $table->dropColumn(['assigned_user_id', 'preferred_notification_channel']);
        });
    }
};
