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
        Schema::table('guarantors', function (Blueprint $table) {
            // First, drop the incorrect foreign key and column
            $table->dropForeign(['borrower_id']);
            $table->dropColumn('borrower_id');

            // Now, add the correct foreign key to link to the loans table
            // We add it after the 'id' column for good structure.
            $table->foreignId('loan_id')->after('id')->constrained()->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guarantors', function (Blueprint $table) {
            //
        });
    }
};
