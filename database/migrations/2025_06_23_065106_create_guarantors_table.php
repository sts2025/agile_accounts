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
        Schema::create('guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrower_id')->constrained('borrowers'); // Foreign Key to borrowers
            $table->string('first_name');
            $table->string('last_name');
            $table->string('nin', 50)->nullable()->unique(); // Guarantor NIN, nullable as might not always have it, unique for simplicity
            $table->string('phone_number', 20);
            $table->string('address');
            $table->string('relationship_to_borrower', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guarantors');
    }
};