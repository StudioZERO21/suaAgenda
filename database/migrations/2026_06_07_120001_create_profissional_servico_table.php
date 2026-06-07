<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profissional_servico', function (Blueprint $table) {
            $table->foreignUuid('profissional_id')->constrained('profissionais')->cascadeOnDelete();
            $table->foreignUuid('servico_id')->constrained('servicos')->cascadeOnDelete();
            $table->primary(['profissional_id', 'servico_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profissional_servico');
    }
};
