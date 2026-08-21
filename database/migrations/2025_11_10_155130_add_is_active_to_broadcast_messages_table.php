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
        // Guarded: the sibling create_broadcast_messages_table migration was
        // fixed to add this column itself when creating the table fresh, so
        // this migration is now only needed for databases where the table
        // already existed without it.
        if (Schema::hasTable('broadcast_messages') && !Schema::hasColumn('broadcast_messages', 'is_active')) {
            Schema::table('broadcast_messages', function (Blueprint $table) {
                $table->boolean('is_active')->default(false)->after('body');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('broadcast_messages', 'is_active')) {
            Schema::table('broadcast_messages', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
