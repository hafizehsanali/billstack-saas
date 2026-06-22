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
       Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
            $table->boolean('is_platform_admin')->default(false)->after('tenant_id');
            $table->timestamp('terms_accepted_at')->nullable()->after('email_verified_at');
            $table->boolean('requires_password_setup')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'requires_password_setup',
                'terms_accepted_at',
                'is_platform_admin',
            ]);
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};
