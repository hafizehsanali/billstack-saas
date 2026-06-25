<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('business_modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->text('description')->nullable();
            $table->string('category')->default('operations');
            $table->boolean('is_core')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('business_module_business_preset', function (Blueprint $table) {
            $table->foreignId('business_preset_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('business_module_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['business_preset_id', 'business_module_id'], 'preset_module_primary');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('business_preset_id')
                ->nullable()
                ->after('slug')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::create('tenant_business_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('business_module_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->string('source')->default('manual');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'business_module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_business_modules');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_preset_id');
        });

        Schema::dropIfExists('business_module_business_preset');
        Schema::dropIfExists('business_modules');
        Schema::dropIfExists('business_presets');
    }
};
