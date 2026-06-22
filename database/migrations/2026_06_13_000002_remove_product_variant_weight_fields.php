<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['weight', 'weight_unit'],
                fn (string $column) => Schema::hasColumn('product_variants', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('weight', 10, 3)->nullable();
            $table->string('weight_unit', 10)->default('kg');
        });
    }
};
