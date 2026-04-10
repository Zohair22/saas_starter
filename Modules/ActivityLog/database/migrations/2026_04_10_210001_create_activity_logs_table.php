<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tenants::class, 'tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->nullableMorphs('subject');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
