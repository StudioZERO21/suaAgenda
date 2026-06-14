<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('plan_slug')->nullable();
            $table->foreign('plan_slug')->references('slug')->on('plans')->nullOnDelete();
            $table->enum('status', [
                'trial',
                'active',
                'grace',       // vencida, dentro do período de graça
                'suspended',   // bloqueada por inadimplência (7+ dias)
                'cancelled',   // cancelada (30+ dias ou manual)
                'past_due',    // pagamento atrasado
            ])->default('trial');
            $table->date('trial_ends_at')->nullable();
            $table->date('current_period_start')->nullable();
            $table->date('current_period_end')->nullable();
            $table->date('anniversary_day')->nullable();  // dia do mês de cobrança
            $table->date('suspended_at')->nullable();
            $table->date('cancelled_at')->nullable();
            $table->string('gateway')->nullable();              // asaas, mercadopago, stripe
            $table->string('gateway_customer_id')->nullable(); // ID do cliente no gateway
            $table->decimal('monthly_amount', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
