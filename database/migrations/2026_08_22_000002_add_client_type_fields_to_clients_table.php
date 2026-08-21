<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Business accounts, per the BKR spec's "Adding a Business Account".
     * Rather than a separate accounts table, a business is just a Client
     * with client_type = 'business' plus a couple of extra fields — every
     * existing loan/savings/shares/FD flow already keys off client_id, so
     * this keeps a business account fully compatible with the rest of the
     * app instead of requiring a parallel set of controllers/views.
     * Existing rows default to 'individual' so nothing already in the
     * system silently becomes a business account.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('clients', 'client_type')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('client_type', 20)->default('individual')->after('name');
                $table->string('business_name')->nullable()->after('client_type');
                $table->string('business_registration_number')->nullable()->after('business_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clients', 'client_type')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn(['client_type', 'business_name', 'business_registration_number']);
            });
        }
    }
};
