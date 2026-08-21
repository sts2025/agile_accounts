<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional bank/till identifiers for asset accounts that represent a
     * physical cash point or bank account (Cash on Hand, Bank Account, and
     * any others a manager adds) — lets an institution with more than one
     * till or bank account tell them apart beyond just the account name.
     * Meaningless for non-asset accounts, so left null there.
     */
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('chart_of_accounts', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('description');
            }
            if (!Schema::hasColumn('chart_of_accounts', 'external_account_number')) {
                $table->string('external_account_number')->nullable()->after('bank_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'external_account_number']);
        });
    }
};
