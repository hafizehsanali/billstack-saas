<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_subscription_invoices', function (Blueprint $table) {
            $table->foreignId('platform_offer_id')
                ->nullable()
                ->after('tenant_subscription_id')
                ->constrained()
                ->nullOnDelete();
            $table->string('offer_code')->nullable()->after('platform_offer_id');
        });
    }

    public function down(): void
    {
        Schema::table('platform_subscription_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('platform_offer_id');
            $table->dropColumn('offer_code');
        });
    }
};
