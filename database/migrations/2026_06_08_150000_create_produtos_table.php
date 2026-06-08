<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('nome');
            $table->string('sku')->nullable();
            $table->string('categoria')->nullable();
            $table->decimal('preco', 8, 2)->default(0);
            $table->decimal('custo', 8, 2)->nullable();
            $table->integer('estoque')->default(0);
            $table->integer('estoque_min')->default(5);
            $table->string('unidade')->default('un');
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
