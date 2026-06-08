<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamentos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('agendamento_id')->nullable()->constrained('agendamentos')->nullOnDelete();
            $table->foreignUuid('venda_id')->nullable()->constrained('vendas')->nullOnDelete();
            $table->enum('tipo', ['receita', 'despesa'])->default('receita');
            $table->string('descricao');
            $table->string('categoria')->nullable();
            $table->decimal('valor', 10, 2);
            $table->date('data');
            $table->enum('status', ['pendente', 'pago', 'cancelado'])->default('pendente');
            $table->string('metodo_pagamento')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos');
    }
};
