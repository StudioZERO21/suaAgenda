<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('autenticação', function () {
    it('exibe a página de login', function () {
        $this->get(route('login'))
            ->assertStatus(200)
            ->assertSee('Login');
    });

    it('redireciona usuário logado para dashboard', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    });

    it('realiza login com credenciais válidas', function () {
        $user = User::factory()->create([
            'password' => bcrypt('senha123'),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'senha123',
        ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    });

    it('rejeita credenciais inválidas', function () {
        $user = User::factory()->create([
            'password' => bcrypt('senha123'),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'senhaerrada',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('valida campos obrigatórios no login', function () {
        $this->post(route('login'), [])
            ->assertSessionHasErrors(['email', 'password']);
    });

    it('realiza logout com sucesso', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    });

    it('exibe a página de registro', function () {
        $this->get(route('register'))
            ->assertStatus(200)
            ->assertSee('Criar Conta');
    });

    it('cria conta nova com empresa e role admin_empresa', function () {
        Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

        $this->post(route('register'), [
            'name' => 'João Teste',
            'email' => 'joao@teste.com',
            'password' => 'Senha@1234',
            'password_confirmation' => 'Senha@1234',
            'company_name' => 'Barbearia do João',
            'lgpd_consent' => '1',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'joao@teste.com')->first();
        expect($user)->not->toBeNull();
        expect($user->empresa_id)->not->toBeNull();
        expect($user->hasRole('admin_empresa'))->toBeTrue();

        $this->assertAuthenticatedAs($user);
    });

    it('valida campos obrigatórios no registro', function () {
        $this->post(route('register'), [])
            ->assertSessionHasErrors(['name', 'email', 'password', 'company_name', 'lgpd_consent']);
    });

    it('protege dashboard de usuários não autenticados', function () {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    });
});
