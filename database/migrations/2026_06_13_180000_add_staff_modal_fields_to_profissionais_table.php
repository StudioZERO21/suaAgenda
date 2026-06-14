<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profissionais', function (Blueprint $table) {
            $table->string('email', 150)->nullable()->after('name');
            $table->json('especialidades')->nullable()->after('especialidade');
            $table->string('status', 20)->default('ativo')->after('ativo');
        });
    }

    public function down(): void
    {
        Schema::table('profissionais', function (Blueprint $table) {
            $table->dropColumn(['email', 'especialidades', 'status']);
        });
    }
};
