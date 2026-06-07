<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->unsignedSmallInteger('duracao_minutos')->default(30);
            $table->decimal('preco', 8, 2)->default(0);
            $table->string('categoria')->nullable();
            $table->string('cor', 7)->default('#1a1a1a');
            $table->boolean('ativo')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicos');
    }
};
