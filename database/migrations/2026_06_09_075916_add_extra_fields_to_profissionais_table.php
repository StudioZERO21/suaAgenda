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
            $table->string('cor', 7)->nullable()->after('especialidade');
            $table->string('phone', 25)->nullable()->after('cor');
            $table->date('admissao')->nullable()->after('phone');
            $table->string('instagram', 100)->nullable()->after('admissao');
            $table->string('tiktok', 100)->nullable()->after('instagram');
            $table->string('facebook', 150)->nullable()->after('tiktok');
        });
    }

    public function down(): void
    {
        Schema::table('profissionais', function (Blueprint $table) {
            $table->dropColumn(['cor', 'phone', 'admissao', 'instagram', 'tiktok', 'facebook']);
        });
    }
};
