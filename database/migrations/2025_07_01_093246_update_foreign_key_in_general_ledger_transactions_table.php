<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('general_ledger_transactions', function (Blueprint $table) {
            // Drop the old foreign key constraint
            $table->dropForeign(['loan_id']);

            // Add the new foreign key constraint with onDelete('cascade')
            $table->foreign('loan_id')
                  ->references('id')
                  ->on('loans')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_ledger_transactions', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['loan_id']);

            // Re-add the original foreign key constraint without cascade on delete
            $table->foreign('loan_id')
                  ->references('id')
                  ->on('loans');
        });
    }
};