<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regra_catalogo', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('codigo', 60)->unique();
            $table->string('nome', 120);
            $table->string('descricao', 300)->nullable();
            $table->string('categoria', 60)->default('Geral');
            $table->json('params_schema');
            $table->json('params_default');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('company_regras', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('regra_catalogo_id')->constrained('regra_catalogo')->cascadeOnDelete();
            $table->boolean('ativo')->default(false);
            $table->json('params')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'regra_catalogo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_regras');
        Schema::dropIfExists('regra_catalogo');
    }
};
