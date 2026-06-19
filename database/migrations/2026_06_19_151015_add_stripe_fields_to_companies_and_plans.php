<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('stripe_customer_id')->nullable()->after('plano');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
        });

        Schema::table('plans', function (Blueprint $table): void {
            $table->string('stripe_price_id')->nullable()->after('preco');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn(['stripe_customer_id', 'stripe_subscription_id']);
        });

        Schema::table('plans', function (Blueprint $table): void {
            $table->dropColumn('stripe_price_id');
        });
    }
};
