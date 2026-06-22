<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_items', 'regular_price')) {
                $table->decimal('regular_price', 12, 2)->nullable()->after('price');
            }

            if (! Schema::hasColumn('invoice_items', 'item_savings')) {
                $table->decimal('item_savings', 12, 2)->default(0)->after('regular_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $columns = array_filter(
                ['regular_price', 'item_savings'],
                fn (string $column) => Schema::hasColumn('invoice_items', $column)
            );

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
