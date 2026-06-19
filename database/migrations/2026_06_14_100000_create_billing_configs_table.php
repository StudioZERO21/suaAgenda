<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_configs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('gateway')->default('asaas');
            $table->text('credentials')->nullable(); // encrypted:array cast — must be text, not json
            $table->integer('grace_warning_days')->default(3);
            $table->integer('grace_suspend_days')->default(7);
            $table->integer('grace_cancel_days')->default(30);
            $table->string('notification_channel_billing')->default('email');   // email ou whatsapp
            $table->string('notification_channel_cancel')->default('whatsapp'); // após inadimplência prolongada
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_configs');
    }
};
