<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venda_itens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('venda_id')->constrained('vendas')->cascadeOnDelete();
            $table->foreignUuid('produto_id')->nullable()->constrained('produtos')->nullOnDelete();
            $table->foreignUuid('servico_id')->nullable()->constrained('servicos')->nullOnDelete();
            $table->string('descricao');
            $table->integer('qtd')->default(1);
            $table->decimal('preco_unit', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venda_itens');
    }
};
