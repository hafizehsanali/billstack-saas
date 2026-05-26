<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {

            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('supplier_id')
                ->constrained()
                ->cascadeOnDelete();

            // Purchase invoice/reference number
            $table->string('purchase_no')->unique();
             // Purchase date
            $table->date('purchase_date');

            // Financials
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('extra_expense', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            
             // Status
            $table->string('status')->default('unpaid');
           

            // Notes
            $table->text('notes')->nullable();

           

            $table->timestamps();

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};