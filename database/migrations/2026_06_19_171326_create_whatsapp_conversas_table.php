<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('direction', 10);          // inbound | outbound
            $table->string('from_number', 30);
            $table->string('to_number', 30);
            $table->text('body');
            $table->string('twilio_sid', 100)->nullable()->unique();
            $table->string('status', 20)->default('received'); // received | sent | failed
            $table->foreignUuid('company_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['from_number', 'created_at']);
            $table->index(['to_number', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversas');
    }
};
