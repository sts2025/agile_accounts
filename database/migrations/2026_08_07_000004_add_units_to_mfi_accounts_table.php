<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shares are held in units (e.g. "40 shares"), separately from their
     * monetary value (balance = units * share_value). Savings and loan
     * accounts simply leave this at 0 and ignore it.
     */
    public function up(): void
    {
        Schema::table('mfi_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('mfi_accounts', 'units')) {
                $table->decimal('units', 15, 4)->default(0)->after('balance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mfi_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('mfi_accounts', 'units')) {
                $table->dropColumn('units');
            }
        });
    }
};
