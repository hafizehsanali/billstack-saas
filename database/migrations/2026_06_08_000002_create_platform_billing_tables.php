<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('platform_offer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('offer_code')->nullable();
            $table->string('invoice_no')->unique();
            $table->string('billing_period');
            $table->string('billing_cycle')->default('monthly');
            $table->unsignedInteger('subtotal_cents')->default(0);
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('tax_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);
            $table->unsignedInteger('paid_cents')->default(0);
            $table->unsignedInteger('balance_cents')->default(0);
            $table->string('status')->default('unpaid');
            $table->date('issued_on');
            $table->date('due_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('platform_subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_subscription_invoice_id');
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->string('payment_method')->default('manual');
            $table->string('reference_no')->nullable();
            $table->date('paid_on');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'paid_on']);
            $table->foreign('platform_subscription_invoice_id', 'ps_payments_invoice_fk')
                ->references('id')
                ->on('platform_subscription_invoices')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_subscription_payments');
        Schema::dropIfExists('platform_subscription_invoices');
    }
};
