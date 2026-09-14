<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BusinessSettingsController::update() has been saving to
     * company_email, company_address, and opening_balance since it was
     * written, but none of the three ever had a real migration creating
     * them on loan_managers — only company_name, company_phone, and
     * company_logo_path (added 2025_10_13) actually exist. Every Business
     * Settings save has therefore been throwing a SQL "Unknown column"
     * error on the underlying UPDATE, which also means company_name/phone
     * never persisted either since it's all one statement.
     */
    public function up(): void
    {
        Schema::table('loan_managers', function (Blueprint $table) {
            if (!Schema::hasColumn('loan_managers', 'company_email')) {
                $table->string('company_email')->nullable()->after('company_phone');
            }
            if (!Schema::hasColumn('loan_managers', 'company_address')) {
                $table->string('company_address', 500)->nullable()->after('company_email');
            }
            if (!Schema::hasColumn('loan_managers', 'opening_balance')) {
                $table->decimal('opening_balance', 15, 2)->default(0)->after('company_logo_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('loan_managers', function (Blueprint $table) {
            $table->dropColumn(['company_email', 'company_address', 'opening_balance']);
        });
    }
};
