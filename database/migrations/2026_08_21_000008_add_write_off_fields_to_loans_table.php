<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('loans', 'write_off_reason')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->text('write_off_reason')->nullable()->after('disbursement_journal_entry_id');
                $table->foreignId('written_off_by')->nullable()->after('write_off_reason')->constrained('users')->nullOnDelete();
                $table->timestamp('written_off_at')->nullable()->after('written_off_by');
                $table->foreignId('write_off_journal_entry_id')->nullable()->after('written_off_at')
                    ->constrained('journal_entries')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('loans', 'write_off_reason')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->dropForeign(['written_off_by']);
                $table->dropForeign(['write_off_journal_entry_id']);
                $table->dropColumn(['write_off_reason', 'written_off_by', 'written_off_at', 'write_off_journal_entry_id']);
            });
        }
    }
};
