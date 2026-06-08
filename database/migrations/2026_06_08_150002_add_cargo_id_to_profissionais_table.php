<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('profissionais', 'cargo_id')) {
            return;
        }

        Schema::table('profissionais', function (Blueprint $table) {
            $table->foreignUuid('cargo_id')->nullable()->after('company_id')
                ->constrained('cargos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('profissionais', function (Blueprint $table) {
            $table->dropForeignIfExists(['cargo_id']);
            $table->dropColumnIfExists('cargo_id');
        });
    }
};
