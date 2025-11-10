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
        Schema::table('loan_managers', function (Blueprint $table) {
            // Adds the currency column with a default of UGX
            $table->string('currency_symbol', 3)->default('UGX')->after('company_logo_path');
            
            // Adds the support phone number with a hardcoded default
            $table->string('support_phone', 50)->default('0740859082')->after('currency_symbol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_managers', function (Blueprint $table) {
            $table->dropColumn('currency_symbol');
            $table->dropColumn('support_phone');
        });
    }
};