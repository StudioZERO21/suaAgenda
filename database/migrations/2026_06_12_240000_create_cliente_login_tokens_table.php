<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_login_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->string('channel', 20)->default('email');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('created_ip', 45)->nullable();
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_login_tokens');
    }
};
