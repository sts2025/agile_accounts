<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            // This is the foreign key to link a client to a Loan Manager.
            // It connects to the 'id' on the 'users' table.
            $table->foreignId('loan_manager_id')->constrained('users')->onDelete('cascade');

            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone_number');
            $table->text('address')->nullable();
            
            $table->timestamps(); // Creates `created_at` and `updated_at` columns
            $table->softDeletes(); // Adds a `deleted_at` column for soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
