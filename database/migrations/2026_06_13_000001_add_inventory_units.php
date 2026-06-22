<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('units')) {
            Schema::create('units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name', 100);
                $table->string('symbol', 20);
                $table->string('description', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['tenant_id', 'name']);
            });
        }

        Schema::table('product_variants', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variants', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('barcode')->constrained('units')->nullOnDelete();
                $table->foreignId('purchase_unit_id')->nullable()->after('unit_id')->constrained('units')->nullOnDelete();
                $table->decimal('purchase_unit_factor', 15, 3)->default(1)->after('purchase_unit_id');
                $table->decimal('purchase_unit_price', 15, 2)->nullable()->after('purchase_unit_factor');
            }
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_items', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('product_variant_id')->constrained('units')->nullOnDelete();
                $table->decimal('unit_factor', 15, 3)->default(1)->after('unit_id');
                $table->decimal('base_quantity', 15, 3)->default(0)->after('quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn(['unit_factor', 'base_quantity']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropConstrainedForeignId('purchase_unit_id');
            $table->dropColumn(['purchase_unit_factor', 'purchase_unit_price']);
        });

        Schema::dropIfExists('units');
    }
};
