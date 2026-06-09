<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE agendamentos MODIFY COLUMN status ENUM('pendente','confirmado','finalizado','cancelado','em_atendimento') DEFAULT 'pendente'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE agendamentos SET status = 'pendente' WHERE status = 'em_atendimento'");
            DB::statement("ALTER TABLE agendamentos MODIFY COLUMN status ENUM('pendente','confirmado','finalizado','cancelado') DEFAULT 'pendente'");
        }
    }
};
