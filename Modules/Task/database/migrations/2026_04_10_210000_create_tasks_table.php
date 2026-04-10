<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Project\Models\Project;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tenants::class, 'tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignIdFor(Project::class, 'project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('open');
            $table->string('priority')->default('medium');
            $table->timestamp('due_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'project_id', 'status']);
            $table->index(['tenant_id', 'assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
