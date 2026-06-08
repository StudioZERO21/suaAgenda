<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->string('slug')->primary();
            $table->string('nome');
            $table->decimal('preco', 8, 2);
            $table->integer('max_profissionais');    // -1 = ilimitado
            $table->integer('whatsapp_mensal');      // -1 = ilimitado
            $table->integer('sms_mensal');           // -1 = ilimitado
            $table->integer('max_whatsapp_overage'); // -1 = ilimitado
            $table->json('features');
            $table->string('color')->default('#6b7280');
            $table->boolean('popular')->default(false);
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
