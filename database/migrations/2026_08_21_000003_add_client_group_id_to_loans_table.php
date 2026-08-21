<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tags a loan as issued to a client group. client_id is deliberately
     * left required/unchanged — it stays the representative/signer on the
     * loan so the huge amount of existing code that assumes loan->client
     * exists (payments, receipts, reports, statements) keeps working
     * untouched. This column is purely additive metadata for group
     * lending reporting and joint-liability visibility.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('loans', 'client_group_id')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->foreignId('client_group_id')->nullable()->after('client_id')
                    ->constrained('client_groups')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('loans', 'client_group_id')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->dropForeign(['client_group_id']);
                $table->dropColumn('client_group_id');
            });
        }
    }
};
