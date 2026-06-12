<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['super_admin', 'admin_empresa', 'gestor', 'analista'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // ── Super Admin (sem empresa) ──────────────────────────────
        $super = User::firstOrCreate(
            ['email' => 'adrianoelite@msn.com'],
            ['name' => 'Super Admin', 'password' => bcrypt('StudioZERO21!'), 'ativo' => true]
        );
        $super->syncRoles(['super_admin']);

        // ── Barbearia Teste ────────────────────────────────────────
        $company = Company::firstOrCreate(
            ['slug' => 'barbearia-teste'],
            [
                'name' => 'Barbearia Teste',
                'plano' => 'trial',
                'plan_slug' => 'starter',
                'lgpd_consent' => true,
                'trial_ends_at' => now()->addDays(30),
                'ativo' => true,
            ]
        );

        if (! $company->plan_slug) {
            $company->update(['plan_slug' => 'starter']);
        }

        // Admin vinculado à Barbearia Teste
        $admin = User::firstOrCreate(
            ['email' => 'adrianoelite1980@gmail.com'],
            ['name' => 'Adriano Santos', 'password' => bcrypt('StudioZERO21!'), 'ativo' => true, 'empresa_id' => $company->id]
        );
        if ($admin->empresa_id !== $company->id) {
            $admin->update(['empresa_id' => $company->id]);
        }
        $admin->syncRoles(['admin_empresa']);

        // Funcionário 1 — Gestor
        $func1 = User::firstOrCreate(
            ['email' => 'carlos@barbearia.test'],
            ['name' => 'Carlos Silva', 'password' => bcrypt('StudioZERO21!'), 'ativo' => true, 'empresa_id' => $company->id]
        );
        $func1->syncRoles(['gestor']);

        // Funcionário 2 — Analista
        $func2 = User::firstOrCreate(
            ['email' => 'joao@barbearia.test'],
            ['name' => 'João Barbeiro', 'password' => bcrypt('StudioZERO21!'), 'ativo' => true, 'empresa_id' => $company->id]
        );
        $func2->syncRoles(['analista']);

        // Cliente de teste (sem role)
        User::firstOrCreate(
            ['email' => 'maria@cliente.test'],
            ['name' => 'Maria Cliente', 'password' => bcrypt('StudioZERO21!'), 'ativo' => true, 'empresa_id' => $company->id]
        );

        $this->command->info('✓ Barbearia Teste + 5 usuários criados com sucesso.');
    }
}
