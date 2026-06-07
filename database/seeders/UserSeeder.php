<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
class UserSeeder extends Seeder {
    public function run(): void {
        foreach (['super_admin','admin_empresa','gestor','analista'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $super = User::firstOrCreate(
            ['email' => 'adrianoelite@msn.com'],
            ['name' => 'Super Admin', 'password' => bcrypt('StudioZERO21!'), 'ativo' => true]
        );
        $super->syncRoles(['super_admin']);
        $admin = User::firstOrCreate(
            ['email' => 'adrianoelite1980@gmail.com'],
            ['name' => 'Admin Empresa', 'password' => bcrypt('StudioZERO21!'), 'ativo' => true]
        );
        $admin->syncRoles(['admin_empresa']);
        $this->command->info('Usuarios criados com sucesso.');
    }
}