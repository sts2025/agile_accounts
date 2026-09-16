<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Multi-branch support. A tenant (LoanManager) can operate more than one
     * office/branch; staff, clients, and loans can each optionally be tagged
     * with a branch for reporting and cash-balance segregation. branch_id is
     * nullable everywhere so existing single-branch tenants keep working
     * exactly as before — branches are opt-in.
     */
    public function up(): void
    {
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_manager_id')->constrained('loan_managers')->cascadeOnDelete();
                $table->string('name');
                $table->string('code', 20)->nullable();
                $table->string('address', 500)->nullable();
                $table->string('phone', 30)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        foreach (['users', 'clients', 'loans'] as $table) {
            if (!Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->foreignId('branch_id')->nullable()->after('loan_manager_id')
                        ->constrained('branches')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['users', 'clients', 'loans'] as $table) {
            if (Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropForeign([$table === 'users' ? 'users_branch_id_foreign' : $table . '_branch_id_foreign']);
                    $blueprint->dropColumn('branch_id');
                });
            }
        }

        Schema::dropIfExists('branches');
    }
};
