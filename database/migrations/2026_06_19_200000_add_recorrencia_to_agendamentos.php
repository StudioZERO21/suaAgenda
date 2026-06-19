<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->boolean('recorrente')->default(false)->after('aprovacao_manual');
            $table->string('recorrencia_tipo', 20)->nullable()->after('recorrente');
            $table->unsignedTinyInteger('recorrencia_total')->nullable()->after('recorrencia_tipo');
            $table->date('recorrencia_ate')->nullable()->after('recorrencia_total');
            $table->uuid('recorrencia_pai_id')->nullable()->after('recorrencia_ate');
            $table->index('recorrencia_pai_id');
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->dropIndex(['recorrencia_pai_id']);
            $table->dropColumn(['recorrente', 'recorrencia_tipo', 'recorrencia_total', 'recorrencia_ate', 'recorrencia_pai_id']);
        });
    }
};
