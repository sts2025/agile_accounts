<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Every other table in this app (clients, loans, expenses, bank/cash
     * transactions, staff...) stores loan_managers.id in its
     * loan_manager_id column. The mfi_products / mfi_accounts /
     * mfi_transactions tables were built against a different convention
     * (users.id) and need to be brought in line, both in the data that's
     * already there and in the foreign key itself.
     *
     * This migration:
     *   1. Rewrites existing loan_manager_id values on the three mfi_*
     *      tables from users.id to the matching loan_managers.id.
     *   2. Re-points the foreign key from `users` to `loan_managers`.
     *
     * It is safe to run even if these tables are empty (a fresh install),
     * and safe to run more than once (the UPDATE...JOIN only touches rows
     * whose loan_manager_id still matches a users.id via loan_managers.user_id;
     * once converted, a second run finds nothing to change for those rows).
     */
    public function up(): void
    {
        $tables = ['mfi_products', 'mfi_accounts', 'mfi_transactions'];

        foreach ($tables as $table) {
            // 1. Drop the FK pointing at `users` (Laravel's default constraint
            // name for foreignId('loan_manager_id')->constrained('users')).
            // dropForeign() takes the constraint name as a plain string here —
            // passing it wrapped in an array makes Laravel treat it as a
            // column list and re-derive (and double-prefix) the name instead.
            if ($this->foreignKeyExists($table, $table . '_loan_manager_id_foreign')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->dropForeign($table . '_loan_manager_id_foreign');
                });
            }

            // 2. Rewrite existing values: users.id -> loan_managers.id
            DB::statement("
                UPDATE {$table} t
                INNER JOIN loan_managers lm ON lm.user_id = t.loan_manager_id
                SET t.loan_manager_id = lm.id
            ");

            // 3. Re-point the FK at loan_managers (only if not already there).
            if (!$this->foreignKeyExists($table, $table . '_loan_manager_id_foreign')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->foreign('loan_manager_id')
                        ->references('id')->on('loan_managers')
                        ->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Check whether a named foreign key constraint already exists on a
     * table, so this migration can be re-run safely after a partial failure.
     */
    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $result = DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table, $constraintName]);

        return count($result) > 0;
    }

    /**
     * Reverse the migrations: convert back to users.id and re-point the FK
     * at `users`. Only run this if you also revert the controller code.
     */
    public function down(): void
    {
        $tables = ['mfi_products', 'mfi_accounts', 'mfi_transactions'];

        foreach ($tables as $table) {
            if ($this->foreignKeyExists($table, $table . '_loan_manager_id_foreign')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->dropForeign($table . '_loan_manager_id_foreign');
                });
            }

            DB::statement("
                UPDATE {$table} t
                INNER JOIN loan_managers lm ON lm.id = t.loan_manager_id
                SET t.loan_manager_id = lm.user_id
            ");

            if (!$this->foreignKeyExists($table, $table . '_loan_manager_id_foreign')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->foreign('loan_manager_id')
                        ->references('id')->on('users')
                        ->onDelete('cascade');
                });
            }
        }
    }
};
