<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('product_limit')->nullable()->after('free_access_days');
            $table->unsignedInteger('monthly_invoice_limit')->nullable()->after('product_limit');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['product_limit', 'monthly_invoice_limit']);
        });
    }
};
