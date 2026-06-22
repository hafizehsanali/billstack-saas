<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('units', 'description')) {
            Schema::table('units', function (Blueprint $table) {
                $table->string('description', 255)->nullable()->after('symbol');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('units', 'description')) {
            Schema::table('units', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
