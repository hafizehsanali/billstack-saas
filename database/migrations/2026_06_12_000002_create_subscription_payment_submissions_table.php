<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_subscription_invoice_id');
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->string('payment_method');
            $table->string('reference_no', 100);
            $table->date('paid_on');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique('platform_subscription_invoice_id', 'sps_invoice_unique');
            $table->foreign('platform_subscription_invoice_id', 'sps_invoice_fk')
                ->references('id')
                ->on('platform_subscription_invoices')
                ->cascadeOnDelete();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payment_submissions');
    }
};
