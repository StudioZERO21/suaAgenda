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
            // % do sinal capturado no momento do agendamento (de company.settings)
            $table->decimal('sinal_pct', 5, 2)->default(0)->after('valor');
            // valor em R$ do sinal (valor * sinal_pct / 100)
            $table->decimal('sinal_valor', 10, 2)->default(0)->after('sinal_pct');
            // status do sinal
            $table->enum('sinal_status', ['nenhum', 'pendente', 'pago', 'expirado'])->default('nenhum')->after('sinal_valor');
            // ID da cobrança no Asaas
            $table->string('sinal_payment_id')->nullable()->after('sinal_status');
            // URL do checkout no Asaas (invoiceUrl)
            $table->string('sinal_payment_url', 2048)->nullable()->after('sinal_payment_id');
            // timestamp em que o sinal foi pago (confirmado pelo webhook)
            $table->timestamp('sinal_pago_em')->nullable()->after('sinal_payment_url');
            // aprovação manual pelo admin (dispensa sinal, cliente paga integral no dia)
            $table->boolean('aprovacao_manual')->default(false)->after('sinal_pago_em');
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->dropColumn([
                'sinal_pct', 'sinal_valor', 'sinal_status',
                'sinal_payment_id', 'sinal_payment_url', 'sinal_pago_em', 'aprovacao_manual',
            ]);
        });
    }
};
