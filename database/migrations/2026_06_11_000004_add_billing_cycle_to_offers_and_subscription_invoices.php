<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_offers', function (Blueprint $table) {
            $table->string('billing_cycle')->default('both')->after('discount_value');
        });

        Schema::table('platform_subscription_invoices', function (Blueprint $table) {
            $table->string('billing_cycle')->default('monthly')->after('billing_period');
        });
    }

    public function down(): void
    {
        Schema::table('platform_subscription_invoices', function (Blueprint $table) {
            $table->dropColumn('billing_cycle');
        });

        Schema::table('platform_offers', function (Blueprint $table) {
            $table->dropColumn('billing_cycle');
        });
    }
};
