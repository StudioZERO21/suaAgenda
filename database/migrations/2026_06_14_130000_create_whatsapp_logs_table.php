<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('to_phone', 30);
            $table->text('message');
            $table->string('status', 20)->default('sent'); // sent, failed, blocked
            $table->string('event_type', 60)->nullable();  // agendamento_confirmado, lembrete_24h, etc.
            $table->string('sid', 100)->nullable();        // Twilio SID
            $table->string('mes_referencia', 7)->index();  // Y-m, para consulta rápida de quota
            $table->timestamps();

            $table->index(['company_id', 'mes_referencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
