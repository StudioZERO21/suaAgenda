<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('segment')->nullable()->after('whatsapp');
            $table->string('email')->nullable()->after('segment');
            $table->string('phone')->nullable()->after('email');
            $table->string('address')->nullable()->after('phone');
            $table->text('description')->nullable()->after('address');
            $table->string('instagram')->nullable()->after('description');
            $table->string('facebook')->nullable()->after('instagram');
            $table->string('tiktok')->nullable()->after('facebook');
            $table->string('youtube')->nullable()->after('tiktok');
            $table->json('settings')->nullable()->after('youtube');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'segment', 'email', 'phone', 'address', 'description',
                'instagram', 'facebook', 'tiktok', 'youtube', 'settings',
            ]);
        });
    }
};
