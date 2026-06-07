<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['super_admin', 'admin_empresa', 'gestor', 'analista'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $super = User::firstOrCreate(
            ['email' => 'adrianoelite@msn.com'],
            ['name' => 'Super Admin', 'password' => bcrypt('StudioZERO21!'), 'ativo' => true]
        );
        $super->syncRoles(['super_admin']);

        $company = Company::firstOrCreate(
            ['slug' => 'empresa-demo'],
            [
                'name' => 'Empresa Demo',
                'plano' => 'trial',
                'lgpd_consent' => true,
                'trial_ends_at' => now()->addDays(7),
                'ativo' => true,
            ]
        );

        $admin = User::firstOrCreate(
            ['email' => 'adrianoelite1980@gmail.com'],
            ['name' => 'Admin Empresa', 'password' => bcrypt('StudioZERO21!'), 'ativo' => true, 'empresa_id' => $company->id]
        );
        $admin->syncRoles(['admin_empresa']);

        $this->command->info('Usuários e empresa demo criados com sucesso.');
    }
}
