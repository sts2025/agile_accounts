<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Group lending: a manager can bundle existing clients into a group
     * (e.g. a village savings circle) and later tag a loan as issued to
     * that group. client_groups.loan_manager_id stores loan_managers.id,
     * matching the convention used by clients/loans everywhere else.
     */
    public function up(): void
    {
        if (!Schema::hasTable('client_groups')) {
            Schema::create('client_groups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_manager_id')->constrained('loan_managers')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('client_group_client')) {
            Schema::create('client_group_client', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_group_id')->constrained('client_groups')->onDelete('cascade');
                $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
                $table->timestamps();
                $table->unique(['client_group_id', 'client_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_group_client');
        Schema::dropIfExists('client_groups');
    }
};
