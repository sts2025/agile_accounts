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
        Schema::table('loans', function (Blueprint $table) {
            // The pipeline: pending -> approved -> disbursed (or rejected)
            // We default to 'disbursed' to protect your existing historical loans!
            $table->string('approval_status')->default('disbursed')->after('status'); 
            
            // Tracking who approved it and when
            $table->unsignedBigInteger('approved_by')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            
            // Just in case an application is denied
            $table->text('rejection_note')->nullable()->after('approved_at');
            
            // Foreign key to link to the user who approved it
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approval_status', 'approved_by', 'approved_at', 'rejection_note']);
        });
    }
};