<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subscription_items', 'meter_id')) {
            Schema::table('subscription_items', function (Blueprint $table): void {
                $table->string('meter_id')->nullable()->after('stripe_price');
            });
        }

        if (! Schema::hasColumn('subscription_items', 'meter_event_name')) {
            Schema::table('subscription_items', function (Blueprint $table): void {
                $table->string('meter_event_name')->nullable()->after('quantity');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscription_items', 'meter_event_name')) {
            Schema::table('subscription_items', function (Blueprint $table): void {
                $table->dropColumn('meter_event_name');
            });
        }

        if (Schema::hasColumn('subscription_items', 'meter_id')) {
            Schema::table('subscription_items', function (Blueprint $table): void {
                $table->dropColumn('meter_id');
            });
        }
    }
};
