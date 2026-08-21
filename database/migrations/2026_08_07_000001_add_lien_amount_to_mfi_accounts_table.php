<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the lien_amount column that powers the "savings-led lending" model:
     * the portion of a client's savings balance that is frozen as collateral
     * against an active loan. Balance - lien_amount = what a client can withdraw.
     */
    public function up(): void
    {
        Schema::table('mfi_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('mfi_accounts', 'lien_amount')) {
                $table->decimal('lien_amount', 15, 2)->default(0)->after('balance');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mfi_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('mfi_accounts', 'lien_amount')) {
                $table->dropColumn('lien_amount');
            }
        });
    }
};
