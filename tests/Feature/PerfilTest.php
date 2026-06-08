<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor',        'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Teste', 'slug' => 'empresa-teste',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create([
        'empresa_id' => $this->company->id,
        'name' => 'João Silva',
        'email' => 'joao@exemplo.com',
        'password' => Hash::make('SenhaAtual123!'),
    ]);
    $this->user->assignRole('admin_empresa');
});

describe('perfil', function () {
    it('usuário autenticado pode ver perfil', function () {
        $this->actingAs($this->user)
            ->get(route('perfil'))
            ->assertOk()
            ->assertViewIs('perfil.index');
    });

    it('usuário não autenticado é redirecionado', function () {
        $this->get(route('perfil'))->assertRedirect(route('login'));
    });

    it('atualiza nome e email', function () {
        $this->actingAs($this->user)
            ->put(route('perfil.update'), [
                'name' => 'João Atualizado',
                'email' => 'joao.novo@exemplo.com',
            ])
            ->assertRedirect(route('perfil'));

        $this->user->refresh();
        expect($this->user->name)->toBe('João Atualizado');
        expect($this->user->email)->toBe('joao.novo@exemplo.com');
    });

    it('nome é obrigatório', function () {
        $this->actingAs($this->user)
            ->put(route('perfil.update'), ['name' => '', 'email' => 'ok@ok.com'])
            ->assertSessionHasErrors('name');
    });

    it('email deve ser único', function () {
        $outro = User::factory()->create(['email' => 'outro@exemplo.com', 'empresa_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->put(route('perfil.update'), ['name' => 'João', 'email' => 'outro@exemplo.com'])
            ->assertSessionHasErrors('email');
    });

    it('altera senha com senha atual correta', function () {
        $this->actingAs($this->user)
            ->put(route('perfil.update'), [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'current_password' => 'SenhaAtual123!',
                'password' => 'NovaSenha456!',
                'password_confirmation' => 'NovaSenha456!',
            ])
            ->assertRedirect(route('perfil'));

        $this->user->refresh();
        expect(Hash::check('NovaSenha456!', $this->user->password))->toBeTrue();
    });

    it('rejeita senha atual incorreta', function () {
        $this->actingAs($this->user)
            ->put(route('perfil.update'), [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'current_password' => 'SenhaErrada!',
                'password' => 'NovaSenha456!',
                'password_confirmation' => 'NovaSenha456!',
            ])
            ->assertSessionHasErrors('current_password');
    });
});
