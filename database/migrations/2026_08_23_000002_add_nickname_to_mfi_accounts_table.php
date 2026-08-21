<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional label so a client can hold more than one account of the same
     * type without them looking identical in the UI — e.g. a "School Fees"
     * savings account alongside their main savings account. Nothing in the
     * data model ever prevented multiple mfi_accounts per client; this just
     * gives managers a way to tell them apart.
     */
    public function up(): void
    {
        Schema::table('mfi_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('mfi_accounts', 'nickname')) {
                $table->string('nickname')->nullable()->after('account_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mfi_accounts', function (Blueprint $table) {
            $table->dropColumn('nickname');
        });
    }
};
