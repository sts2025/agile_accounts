<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original "create_broadcast_messages_table" migration never
     * actually called Schema::create() (it only tried to add an is_active
     * column to a table that may not have existed), and — since that file
     * was already recorded as run — fixing its content afterwards doesn't
     * make Laravel re-run it. This migration is the real fix: it brings
     * broadcast_messages to the correct shape no matter what state it's
     * currently in (missing entirely, missing is_active, or already fine).
     */
    public function up(): void
    {
        if (!Schema::hasTable('broadcast_messages')) {
            Schema::create('broadcast_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('title');
                $table->text('body');
                $table->boolean('is_active')->default(false);
                $table->timestamps();
            });
            return;
        }

        if (!Schema::hasColumn('broadcast_messages', 'is_active')) {
            Schema::table('broadcast_messages', function (Blueprint $table) {
                $table->boolean('is_active')->default(false)->after('body');
            });
        }
    }

    public function down(): void
    {
        // No-op: this migration only repairs a broken prior state; rolling
        // it back shouldn't drop a table that other (older) migrations
        // believe they own.
    }
};
