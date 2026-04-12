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
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('mfa_enabled')->default(false)->after('is_super_admin');
            $table->text('mfa_secret')->nullable()->after('mfa_enabled');
            $table->json('mfa_recovery_codes')->nullable()->after('mfa_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['mfa_enabled', 'mfa_secret', 'mfa_recovery_codes']);
        });
    }
};
