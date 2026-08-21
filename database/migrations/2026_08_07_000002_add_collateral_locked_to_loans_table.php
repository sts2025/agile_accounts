<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tracks how much of a client's savings THIS loan locked as collateral
     * (mfi_accounts.lien_amount is a running total across all of a client's
     * loans, so we need a per-loan record to release the right amount when
     * this specific loan is paid off, without touching liens held by any
     * other concurrent loan the same client might have).
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (!Schema::hasColumn('loans', 'collateral_locked')) {
                $table->decimal('collateral_locked', 15, 2)->default(0)->after('principal_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (Schema::hasColumn('loans', 'collateral_locked')) {
                $table->dropColumn('collateral_locked');
            }
        });
    }
};
