<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table): void {
            $table->timestamp('lgpd_consent_at')->nullable()->after('lgpd_consent');
            $table->string('lgpd_consent_ip', 45)->nullable()->after('lgpd_consent_at');
            $table->timestamp('anonymized_at')->nullable()->after('lgpd_consent_ip');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table): void {
            $table->dropColumn(['lgpd_consent_at', 'lgpd_consent_ip', 'anonymized_at']);
        });
    }
};
