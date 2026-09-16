<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Two related bugs found while chasing the payment-receipt 500 error:
     *
     * 1. clients.loan_manager_id, loans.loan_manager_id, cash_transfers.loan_manager_id,
     *    and expenses.loan_manager_id have ALWAYS been populated with
     *    loan_managers.id (see LoanManager::clients()/loans()/cashTransfers(),
     *    ExpenseController, etc. — every write path uses
     *    Auth::user()->loanManager->id). But their foreign key constraints were
     *    declared against the `users` table instead of `loan_managers`
     *    (foreignId('loan_manager_id')->constrained('users')). This has been
     *    silently "working" only because `users` has at least as many rows as
     *    `loan_managers` (every tenant owner plus their staff are all users),
     *    so a loan_managers.id almost always happens to also exist as *some*
     *    users.id — just not the right one, and not guaranteed. Worse, the
     *    onDelete('cascade') on clients/loans meant deleting a staff User row
     *    whose id happened to match a tenant's loan_managers.id would have
     *    silently cascade-deleted that tenant's entire client/loan book.
     *    This re-points all four at the table they actually reference.
     *
     * 2. expense_categories.loan_manager_id was referenced everywhere in
     *    ExpenseController (and required for every "Add Expense" save) but no
     *    migration ever created the column — every expense-category query or
     *    save has been throwing a SQL "Unknown column" error. This adds it.
     *
     * Safe to run more than once and safe on a fresh install.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('expense_categories', 'loan_manager_id')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->foreignId('loan_manager_id')->nullable()->after('id')
                    ->constrained('loan_managers')->cascadeOnDelete();
            });
        }

        foreach (['clients', 'loans', 'cash_transfers', 'expenses'] as $table) {
            $this->repointToLoanManagers($table);
        }
    }

    private function repointToLoanManagers(string $table): void
    {
        $constraint = $table . '_loan_manager_id_foreign';

        if ($this->foreignKeyExists($table, $constraint)) {
            $referencedTable = $this->foreignKeyReferencedTable($table, $constraint);

            if ($referencedTable === 'loan_managers') {
                // Already fixed (e.g. this migration re-run after a partial failure).
                return;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($constraint) {
                $blueprint->dropForeign($constraint);
            });
        }

        // Heal any rows that were written back when this column really did
        // mean users.id (an older code path, or hand-entered test data) —
        // same "translate via loan_managers.user_id" trick the mfi_* table
        // fix used. Only touches rows that don't already match a real
        // loan_managers.id, so correctly-written rows are never disturbed.
        DB::statement("
            UPDATE {$table} t
            INNER JOIN loan_managers lm ON lm.user_id = t.loan_manager_id
            LEFT JOIN loan_managers lm2 ON lm2.id = t.loan_manager_id
            SET t.loan_manager_id = lm.id
            WHERE lm2.id IS NULL
        ");

        $orphanCount = DB::table($table)
            ->whereNotNull('loan_manager_id')
            ->whereNotIn('loan_manager_id', function ($query) {
                $query->select('id')->from('loan_managers');
            })
            ->count();

        if ($orphanCount > 0) {
            // Genuinely unresolvable rows (point at neither a real
            // loan_managers.id nor a loan_managers.user_id — e.g. leftover
            // test data from a deleted account). Adding the FK would abort
            // the whole migration, so skip it for this table rather than
            // block every other migration behind it. The table keeps
            // working (the app never relied on the DB-level constraint,
            // only on the column's value), just without FK enforcement
            // until those rows are cleaned up or reassigned by hand.
            echo "  Skipped re-pointing {$table}.loan_manager_id's foreign key — {$orphanCount} row(s) reference a loan_manager_id that doesn't exist. Find them with:\n";
            echo "    SELECT * FROM {$table} WHERE loan_manager_id NOT IN (SELECT id FROM loan_managers);\n";
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->foreign('loan_manager_id')
                ->references('id')->on('loan_managers')
                ->onDelete('cascade');
        });
    }

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

    private function foreignKeyReferencedTable(string $table, string $constraintName): ?string
    {
        $result = DB::select("
            SELECT REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$table, $constraintName]);

        return $result[0]->REFERENCED_TABLE_NAME ?? null;
    }

    public function down(): void
    {
        foreach (['clients', 'loans', 'cash_transfers', 'expenses'] as $table) {
            $constraint = $table . '_loan_manager_id_foreign';
            if ($this->foreignKeyExists($table, $constraint)) {
                Schema::table($table, function (Blueprint $blueprint) use ($constraint) {
                    $blueprint->dropForeign($constraint);
                });
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreign('loan_manager_id')
                    ->references('id')->on('users')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasColumn('expense_categories', 'loan_manager_id')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->dropForeign(['loan_manager_id']);
                $table->dropColumn('loan_manager_id');
            });
        }
    }
};
