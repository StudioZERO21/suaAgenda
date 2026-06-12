<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE agendamentos MODIFY COLUMN status ENUM('pendente','confirmado','finalizado','cancelado','em_atendimento','no_show') DEFAULT 'pendente'");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("UPDATE agendamentos SET status = 'cancelado' WHERE status = 'no_show'");
            DB::statement("ALTER TABLE agendamentos MODIFY COLUMN status ENUM('pendente','confirmado','finalizado','cancelado','em_atendimento') DEFAULT 'pendente'");
        }
    }
};
