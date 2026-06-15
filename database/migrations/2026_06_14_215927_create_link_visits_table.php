<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_visits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->enum('type', ['view', 'booking'])->default('view');
            $table->timestamps();

            $table->index(['company_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_visits');
    }
};
