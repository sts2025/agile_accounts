<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * KYC enhancements: photo, ID document, gender, and next-of-kin details.
     * Guarded per-column so this is safe to run regardless of exactly which
     * columns already exist on a given install.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'gender')) {
                $table->string('gender')->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('clients', 'photo_path')) {
                $table->string('photo_path')->nullable()->after('business_occupation');
            }
            if (!Schema::hasColumn('clients', 'id_document_path')) {
                $table->string('id_document_path')->nullable()->after('photo_path');
            }
            if (!Schema::hasColumn('clients', 'next_of_kin_name')) {
                $table->string('next_of_kin_name')->nullable()->after('id_document_path');
            }
            if (!Schema::hasColumn('clients', 'next_of_kin_phone')) {
                $table->string('next_of_kin_phone')->nullable()->after('next_of_kin_name');
            }
            if (!Schema::hasColumn('clients', 'next_of_kin_relationship')) {
                $table->string('next_of_kin_relationship')->nullable()->after('next_of_kin_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            foreach (['gender', 'photo_path', 'id_document_path', 'next_of_kin_name', 'next_of_kin_phone', 'next_of_kin_relationship'] as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
