<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique('purchases_purchase_no_unique');
            $table->unique(['tenant_id', 'purchase_no'], 'purchases_tenant_purchase_no_unique');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique('purchases_tenant_purchase_no_unique');
            $table->unique('purchase_no', 'purchases_purchase_no_unique');
        });
    }
};
