<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A proper, tenant-scoped chart of accounts. This replaces the old
     * `accounts` table (app/Models/Account.php), which has no
     * loan_manager_id at all — every tenant shared the same global rows,
     * which is why the old GL-posting code was pulled out earlier as
     * unsafe/dead. This table follows the same loan_manager_id convention
     * as clients/loans/mfi_accounts/etc. throughout the rest of the app.
     */
    public function up(): void
    {
        if (!Schema::hasTable('chart_of_accounts')) {
            Schema::create('chart_of_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_manager_id')->constrained('loan_managers')->onDelete('cascade');
                $table->string('code', 20);
                $table->string('name');
                // asset | liability | equity | income | expense
                $table->string('type', 20);
                $table->text('description')->nullable();
                // Seeded/default accounts (is_system = true) can't be deleted,
                // only deactivated, so auto-posting code always has somewhere
                // safe to write to.
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['loan_manager_id', 'code']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
