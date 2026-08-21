<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('clients', 'is_blacklisted')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->boolean('is_blacklisted')->default(false)->after('national_id');
                $table->text('blacklist_reason')->nullable()->after('is_blacklisted');
                $table->timestamp('blacklisted_at')->nullable()->after('blacklist_reason');
                $table->foreignId('blacklisted_by')->nullable()->after('blacklisted_at')
                    ->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clients', 'is_blacklisted')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropForeign(['blacklisted_by']);
                $table->dropColumn(['is_blacklisted', 'blacklist_reason', 'blacklisted_at', 'blacklisted_by']);
            });
        }
    }
};
