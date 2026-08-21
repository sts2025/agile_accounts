<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks the last date interest was posted (credited) to a savings
     * account, so the End-of-Period interest run knows exactly how many
     * days to accrue for and never double-posts the same period.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('mfi_accounts', 'last_interest_posted_at')) {
            Schema::table('mfi_accounts', function (Blueprint $table) {
                $table->date('last_interest_posted_at')->nullable()->after('maturity_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mfi_accounts', 'last_interest_posted_at')) {
            Schema::table('mfi_accounts', function (Blueprint $table) {
                $table->dropColumn('last_interest_posted_at');
            });
        }
    }
};
