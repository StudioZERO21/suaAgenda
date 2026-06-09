<?php

declare(strict_types=1);

use App\Models\Profissional;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignUuid('profissional_id')
                ->nullable()
                ->after('empresa_id')
                ->constrained('profissionais')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeignIdFor(Profissional::class);
            $table->dropColumn('profissional_id');
        });
    }
};
