<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendamentos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('profissional_id')->constrained('profissionais')->cascadeOnDelete();
            $table->foreignUuid('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->dateTime('data_hora');
            $table->unsignedSmallInteger('duracao'); // em minutos
            $table->enum('status', ['pendente', 'confirmado', 'finalizado', 'cancelado'])->default('pendente');
            $table->text('observacao')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'data_hora']);
            $table->index(['profissional_id', 'data_hora']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamentos');
    }
};
