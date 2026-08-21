<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fixed Deposit accounts need a maturity date to know when the term
     * ends and interest becomes payable without penalty. Savings/loan/
     * shares accounts leave this null and ignore it.
     */
    public function up(): void
    {
        Schema::table('mfi_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('mfi_accounts', 'maturity_date')) {
                $table->date('maturity_date')->nullable()->after('units');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mfi_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('mfi_accounts', 'maturity_date')) {
                $table->dropColumn('maturity_date');
            }
        });
    }
};
