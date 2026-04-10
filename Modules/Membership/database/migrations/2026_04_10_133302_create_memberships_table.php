<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Membership\Enums\MembershipRole;
use Modules\Tenant\Models\Tenants;
use Modules\User\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(Tenants::class, 'tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->enum('role', array_column(MembershipRole::cases(), 'value'))->default(MembershipRole::Member->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
