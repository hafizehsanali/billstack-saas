<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_sale_mode')->default('packed')->after('is_online_enabled');
            $table->boolean('allow_loose_sale')->default(false)->after('product_sale_mode');
            $table->foreignId('base_stock_unit_id')->nullable()->after('allow_loose_sale')->constrained('units')->nullOnDelete();
            $table->foreignId('default_purchase_unit_id')->nullable()->after('base_stock_unit_id')->constrained('units')->nullOnDelete();
            $table->decimal('default_purchase_unit_factor', 15, 3)->nullable()->after('default_purchase_unit_id');
            $table->boolean('track_expiry')->default(false)->after('default_purchase_unit_factor');
            $table->boolean('track_batch')->default(false)->after('track_expiry');
            $table->boolean('track_serial')->default(false)->after('track_batch');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('conversion_to_base_unit', 15, 6)->nullable()->after('purchase_unit_price');
        });

        $this->decimalQuantity('products', 'stock_quantity', '15,3');
        $this->decimalQuantity('product_variants', 'stock_quantity', '15,3');
        $this->decimalQuantity('invoice_items', 'quantity', '15,3');
        $this->decimalQuantity('purchase_items', 'quantity', '15,3');
        $this->decimalQuantity('purchase_items', 'base_quantity', '15,3');
        $this->decimalQuantity('sales_return_items', 'quantity', '15,3');
        $this->decimalQuantity('purchase_return_items', 'quantity', '15,3');

        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('batch_number');
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'product_variant_id', 'batch_number'], 'product_batch_unique');
            $table->index(['tenant_id', 'expiry_date']);
        });

        Schema::create('product_serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('serial_number');
            $table->string('status')->default('available');
            $table->foreignId('purchase_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_item_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'serial_number']);
            $table->index(['tenant_id', 'product_variant_id', 'status']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('product_batch_id')->nullable()->after('product_variant_id')->constrained()->nullOnDelete();
            $table->foreignId('product_serial_number_id')->nullable()->after('product_batch_id')->constrained()->nullOnDelete();
            $table->string('batch_number')->nullable()->after('reference_no');
            $table->date('expiry_date')->nullable()->after('batch_number');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_batch_id');
            $table->dropConstrainedForeignId('product_serial_number_id');
            $table->dropColumn(['batch_number', 'expiry_date']);
        });

        Schema::dropIfExists('product_serial_numbers');
        Schema::dropIfExists('product_batches');

        $this->decimalQuantity('purchase_return_items', 'quantity', 'integer');
        $this->decimalQuantity('sales_return_items', 'quantity', 'integer');
        $this->decimalQuantity('purchase_items', 'base_quantity', 'integer');
        $this->decimalQuantity('purchase_items', 'quantity', 'integer');
        $this->decimalQuantity('invoice_items', 'quantity', 'integer');
        $this->decimalQuantity('product_variants', 'stock_quantity', 'integer');
        $this->decimalQuantity('products', 'stock_quantity', 'integer');

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('conversion_to_base_unit');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('base_stock_unit_id');
            $table->dropConstrainedForeignId('default_purchase_unit_id');
            $table->dropColumn([
                'product_sale_mode',
                'allow_loose_sale',
                'default_purchase_unit_factor',
                'track_expiry',
                'track_batch',
                'track_serial',
            ]);
        });
    }

    private function decimalQuantity(string $table, string $column, string $type): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE {$table} MODIFY {$column} ".($type === 'integer' ? 'INT' : "DECIMAL({$type})").' NOT NULL DEFAULT 0');
    }
};
