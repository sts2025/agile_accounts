<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('loan_managers', function (Blueprint $table) {
            // This column stores when the subscription ends
            $table->timestamp('subscription_expires_at')->nullable()->after('company_logo_path');
        });
    }

    public function down()
    {
        Schema::table('loan_managers', function (Blueprint $table) {
            $table->dropColumn('subscription_expires_at');
        });
    }
};