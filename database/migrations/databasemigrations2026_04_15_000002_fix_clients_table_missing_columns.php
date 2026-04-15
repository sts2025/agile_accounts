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
        Schema::table('clients', function (Blueprint $table) {
            // Safely add any columns that are missing from the live database
            if (!Schema::hasColumn('clients', 'email')) {
                $table->string('email')->nullable()->after('name');
            }
            if (!Schema::hasColumn('clients', 'national_id')) {
                $table->string('national_id')->nullable()->after('phone_number');
            }
            if (!Schema::hasColumn('clients', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('national_id');
            }
            if (!Schema::hasColumn('clients', 'business_occupation')) {
                $table->string('business_occupation')->nullable()->after('address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['email', 'national_id', 'date_of_birth', 'business_occupation']);
        });
    }
};