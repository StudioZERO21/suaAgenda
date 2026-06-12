<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrade para spatie teams (company_id) em bancos criados com teams=false.
 * Em instalações novas, create_permission_tables já cria as colunas de team
 * (idempotente via hasColumn).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('roles', 'company_id')) {
            Schema::table('roles', function (Blueprint $table): void {
                $table->uuid('company_id')->nullable()->after('id');
                $table->index('company_id', 'roles_team_foreign_key_index');
                $table->dropUnique('roles_name_guard_name_unique');
                $table->unique(['company_id', 'name', 'guard_name']);
            });
        }

        Schema::table('roles', function (Blueprint $table): void {
            $table->string('cor', 9)->nullable();
            $table->string('descricao', 200)->nullable();
            $table->boolean('is_system')->default(false);
        });

        if (! Schema::hasColumn('model_has_roles', 'company_id')) {
            Schema::table('model_has_roles', function (Blueprint $table): void {
                $table->uuid('company_id')->nullable()->after('role_id');
                $table->index('company_id', 'model_has_roles_team_foreign_key_index');
            });
        }

        if (! Schema::hasColumn('model_has_permissions', 'company_id')) {
            Schema::table('model_has_permissions', function (Blueprint $table): void {
                $table->uuid('company_id')->nullable()->after('permission_id');
                $table->index('company_id', 'model_has_permissions_team_foreign_key_index');
            });
        }

        // Backfill: pivots existentes herdam a empresa do usuário (null p/ super_admin)
        DB::statement(
            'UPDATE model_has_roles
             SET company_id = (SELECT empresa_id FROM users WHERE users.id = model_has_roles.model_uuid)
             WHERE model_type = ? AND company_id IS NULL',
            [User::class]
        );

        // Grupo de acesso por cargo (roles team-scoped)
        Schema::table('cargos', function (Blueprint $table): void {
            $table->unsignedBigInteger('grupo_acesso_id')->nullable()->after('comissao_pct');
            $table->foreign('grupo_acesso_id')->references('id')->on('roles')->nullOnDelete();
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        Schema::table('cargos', function (Blueprint $table): void {
            $table->dropForeign(['grupo_acesso_id']);
            $table->dropColumn('grupo_acesso_id');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn(['cor', 'descricao', 'is_system']);
        });
    }
};
