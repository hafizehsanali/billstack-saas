<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('platform_name')->default('BillStack');
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();
            $table->string('currency_code', 3)->default('PKR');
            $table->text('payment_instructions')->nullable();
            $table->boolean('allow_registration')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
