<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Tipo', 'slug' => 'barbearia-tipo',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');
});

describe('configuracao_tipografia', function () {
    it('admin pode atualizar tipografia', function () {
        $this->actingAs($this->admin)
            ->post(route('configuracoes.tipografia'), [
                'heading_font' => 'montserrat',
                'body_font' => 'dm-sans',
            ])
            ->assertRedirectContains('tipografia');

        $settings = $this->company->fresh()->settings;
        expect($settings['heading_font'])->toBe('montserrat');
        expect($settings['body_font'])->toBe('dm-sans');
    });

    it('gestor não pode atualizar tipografia', function () {
        $this->actingAs($this->gestor)
            ->post(route('configuracoes.tipografia'), [
                'heading_font' => 'montserrat',
                'body_font' => 'dm-sans',
            ])
            ->assertForbidden();
    });

    it('rejeita fonte de título inválida', function () {
        $this->actingAs($this->admin)
            ->post(route('configuracoes.tipografia'), [
                'heading_font' => 'comic-sans',
                'body_font' => 'inter',
            ])
            ->assertSessionHasErrors(['heading_font']);
    });

    it('rejeita fonte de corpo inválida', function () {
        $this->actingAs($this->admin)
            ->post(route('configuracoes.tipografia'), [
                'heading_font' => 'poppins',
                'body_font' => 'times-new-roman',
            ])
            ->assertSessionHasErrors(['body_font']);
    });

    it('admin pode restaurar tipografia para o padrão', function () {
        $this->company->settings = ['heading_font' => 'montserrat', 'body_font' => 'dm-sans'];
        $this->company->save();

        $this->actingAs($this->admin)
            ->post(route('configuracoes.tipografia.reset'))
            ->assertRedirectContains('tipografia');

        $settings = $this->company->fresh()->settings;
        expect(isset($settings['heading_font']))->toBeFalse();
        expect(isset($settings['body_font']))->toBeFalse();
    });

    it('gestor não pode restaurar tipografia', function () {
        $this->actingAs($this->gestor)
            ->post(route('configuracoes.tipografia.reset'))
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->post(route('configuracoes.tipografia'), [
            'heading_font' => 'poppins',
            'body_font' => 'inter',
        ])->assertRedirectContains('login');
    });
});
