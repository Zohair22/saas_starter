<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_usage_counters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->date('period_start');
            $table->unsignedInteger('users_count')->default(0);
            $table->unsignedInteger('projects_count')->default(0);
            $table->unsignedInteger('api_requests_count')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_usage_counters');
    }
};
