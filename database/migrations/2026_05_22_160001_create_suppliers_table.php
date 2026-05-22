<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('suppliers', function (Blueprint $table) {

        $table->id();

        $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

        $table->string('name');

        $table->string('phone')->nullable();

        $table->string('email')->nullable();

        $table->text('address')->nullable();
        $table->text('opening_balance')->nullable();
        
        $table->softDeletes();
        $table->timestamps();

    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
