<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('monthly_price_cents')->default(0);
            $table->unsignedInteger('annual_price_cents')->default(0);
            $table->unsignedInteger('user_limit')->nullable();
            $table->unsignedInteger('trial_days')->default(0);
            $table->unsignedInteger('free_access_days')->nullable();
            $table->unsignedInteger('product_limit')->nullable();
            $table->unsignedInteger('monthly_invoice_limit')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
        });

        Schema::create('feature_plan', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('plan_feature_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->json('limits')->nullable();
            $table->timestamps();

            $table->primary(['subscription_plan_id', 'plan_feature_id']);
        });

        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('status')->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('feature_plan');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('subscription_plans');
    }
};
