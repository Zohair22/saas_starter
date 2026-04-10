<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenants', 'billing_status')) {
                $table->string('billing_status')->nullable()->after('trial_ends_at');
            }

            if (! Schema::hasColumn('tenants', 'grace_period_ends_at')) {
                $table->timestamp('grace_period_ends_at')->nullable()->after('billing_status');
            }

            if (! Schema::hasColumn('tenants', 'delinquent_since')) {
                $table->timestamp('delinquent_since')->nullable()->after('grace_period_ends_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['billing_status', 'grace_period_ends_at', 'delinquent_since']);
        });
    }
};
