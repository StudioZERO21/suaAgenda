<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE agendamentos
            MODIFY COLUMN status
            ENUM('pendente','confirmado','finalizado','cancelado','em_atendimento','no_show','aguardando_sinal')
            NOT NULL DEFAULT 'pendente'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE agendamentos
            MODIFY COLUMN status
            ENUM('pendente','confirmado','finalizado','cancelado','em_atendimento','no_show')
            NOT NULL DEFAULT 'pendente'
        ");
    }
};
