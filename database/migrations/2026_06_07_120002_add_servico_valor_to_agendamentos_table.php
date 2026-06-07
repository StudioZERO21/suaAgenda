<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->foreignUuid('servico_id')->nullable()->constrained('servicos')->nullOnDelete()->after('cliente_id');
            $table->decimal('valor', 8, 2)->nullable()->after('duracao');
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('servico_id');
            $table->dropColumn('valor');
        });
    }
};
