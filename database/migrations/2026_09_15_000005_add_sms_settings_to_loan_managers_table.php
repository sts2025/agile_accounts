<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-tenant SMS gateway settings, so each MFI can send payment/loan
     * alerts to their clients from their own SMS account (their own
     * sender ID, their own bill) rather than a shared platform-wide one.
     * All nullable — a tenant who never fills these in simply doesn't get
     * SMS delivery (NotificationService checks before sending), same as
     * today.
     */
    public function up(): void
    {
        Schema::table('loan_managers', function (Blueprint $table) {
            if (!Schema::hasColumn('loan_managers', 'sms_provider')) {
                $table->string('sms_provider', 30)->nullable()->after('support_phone');
            }
            if (!Schema::hasColumn('loan_managers', 'sms_api_key')) {
                $table->string('sms_api_key')->nullable()->after('sms_provider');
            }
            if (!Schema::hasColumn('loan_managers', 'sms_api_secret')) {
                $table->string('sms_api_secret')->nullable()->after('sms_api_key');
            }
            if (!Schema::hasColumn('loan_managers', 'sms_sender_id')) {
                $table->string('sms_sender_id', 30)->nullable()->after('sms_api_secret');
            }
            if (!Schema::hasColumn('loan_managers', 'sms_username')) {
                // Africa's Talking authenticates with a username + API key
                // pair rather than key + secret; Twilio's "secret" (Auth
                // Token) uses sms_api_secret above instead.
                $table->string('sms_username', 100)->nullable()->after('sms_sender_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('loan_managers', function (Blueprint $table) {
            $table->dropColumn(['sms_provider', 'sms_api_key', 'sms_api_secret', 'sms_sender_id', 'sms_username']);
        });
    }
};
