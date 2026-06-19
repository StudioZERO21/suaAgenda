<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('evolution_instance', 80)->nullable()->after('settings');
            $table->boolean('evolution_connected')->default(false)->after('evolution_instance');
            $table->timestamp('evolution_connected_at')->nullable()->after('evolution_connected');
            $table->unsignedInteger('notif_limit_whatsapp')->nullable()->after('evolution_connected_at');
            $table->unsignedInteger('notif_limit_sms')->nullable()->after('notif_limit_whatsapp');
            $table->unsignedInteger('notif_limit_email')->nullable()->after('notif_limit_sms');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn([
                'evolution_instance',
                'evolution_connected',
                'evolution_connected_at',
                'notif_limit_whatsapp',
                'notif_limit_sms',
                'notif_limit_email',
            ]);
        });
    }
};
