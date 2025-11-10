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
        $table->string('company_name')->nullable()->after('address');
        $table->string('company_phone')->nullable()->after('company_name');
        $table->string('company_logo_path')->nullable()->after('company_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_managers', function (Blueprint $table) {
            //
        });
    }
};
