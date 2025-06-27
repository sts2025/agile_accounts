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
        Schema::create('borrowers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_manager_id')->constrained('loan_managers'); // Foreign Key to loan_managers
            $table->string('first_name');
            $table->string('last_name');
            $table->string('nin', 50)->unique(); // National Identification Number
            $table->string('phone_number', 20);
            $table->string('address');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('occupation');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrowers');
    }
};