<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /**
         * Run the migrations.
         *
         * NOTE: despite the filename, this migration never actually called
         * Schema::create() — it only ran Schema::table(...)->boolean('is_active'),
         * which assumes the table already exists. On a fresh install this would
         * fail with "table not found". Fixed to actually create the table,
         * guarded so it's safe to run whether or not the table already exists
         * in an existing database.
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
            }
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('broadcast_messages');
        }
    };