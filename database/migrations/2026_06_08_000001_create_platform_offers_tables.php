<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_offers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('discount_type');
            $table->unsignedInteger('discount_value');
            $table->unsignedInteger('trial_days')->default(0);
            $table->unsignedInteger('redemption_limit')->nullable();
            $table->unsignedInteger('redeemed_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('offer_subscription_plan', function (Blueprint $table) {
            $table->foreignId('platform_offer_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['platform_offer_id', 'subscription_plan_id'], 'offer_plan_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_subscription_plan');
        Schema::dropIfExists('platform_offers');
    }
};
