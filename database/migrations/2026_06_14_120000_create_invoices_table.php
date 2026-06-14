<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique(); // INV-2026-000001
            $table->enum('status', [
                'pending',
                'paid',
                'overdue',
                'cancelled',
                'refunded',
            ])->default('pending');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->string('gateway')->nullable();
            $table->string('gateway_invoice_id')->nullable();  // ID da cobrança no gateway
            $table->string('gateway_payment_url')->nullable(); // link de pagamento
            $table->string('payment_method')->nullable();      // pix, boleto, card
            $table->text('notes')->nullable();
            $table->json('meta')->nullable(); // dados extras do gateway
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
