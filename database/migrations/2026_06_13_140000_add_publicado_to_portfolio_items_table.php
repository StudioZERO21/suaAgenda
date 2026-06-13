<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_items', function (Blueprint $table): void {
            $table->boolean('publicado')->default(false)->after('destaque');
        });

        // Fotos já existentes permanecem visíveis na galeria pública.
        DB::table('portfolio_items')->whereNotNull('imagem_path')->update(['publicado' => true]);
    }

    public function down(): void
    {
        Schema::table('portfolio_items', function (Blueprint $table): void {
            $table->dropColumn('publicado');
        });
    }
};
