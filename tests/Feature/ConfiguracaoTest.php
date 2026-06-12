<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor',        'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista',      'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Teste',
        'slug' => 'empresa-teste',
        'plano' => 'trial',
        'whatsapp' => '(11) 99999-0000',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('configuracoes preferencias', function () {
    it('admin pode ver configurações', function () {
        $this->actingAs($this->admin)
            ->get(route('configuracoes'))
            ->assertOk()
            ->assertSee('Configurações')
            ->assertSee('Paleta de Cores');
    });

    it('gestor pode ver configurações', function () {
        $this->actingAs($this->gestor)
            ->get(route('configuracoes'))
            ->assertOk();
    });

    it('analista não pode ver configurações (sem permissão)', function () {
        $this->actingAs($this->analista)
            ->get(route('configuracoes'))
            ->assertForbidden();
    });

    it('admin pode salvar preferências de tema', function () {
        $this->actingAs($this->admin)
            ->put(route('configuracoes.preferencias'), [
                'theme_palette' => 'B',
                'dark_mode' => '1',
                'tab' => 'tema',
            ])
            ->assertRedirect(route('configuracoes', ['tab' => 'tema']));

        $settings = $this->company->fresh()->resolvedSettings();

        expect($settings['theme_palette'])->toBe('B');
        expect($settings['dark_mode'])->toBeTrue();
    });

    it('gestor não pode atualizar preferências', function () {
        $this->actingAs($this->gestor)
            ->put(route('configuracoes.preferencias'), [
                'theme_palette' => 'C',
            ])
            ->assertForbidden();
    });

    it('analista não pode atualizar preferências', function () {
        $this->actingAs($this->analista)
            ->put(route('configuracoes.preferencias'), [
                'theme_palette' => 'C',
            ])
            ->assertForbidden();
    });

    it('admin pode restaurar tipografia padrão', function () {
        $this->company->update([
            'settings' => [
                'heading_font' => 'montserrat',
                'body_font' => 'lato',
            ],
        ]);

        $this->actingAs($this->admin)
            ->post(route('configuracoes.tipografia.reset'))
            ->assertRedirect(route('configuracoes', ['tab' => 'tipografia']));

        $raw = $this->company->fresh()->settings ?? [];

        expect($raw)->not->toHaveKey('heading_font');
        expect($raw)->not->toHaveKey('body_font');
        expect($this->company->fresh()->resolvedSettings()['heading_font'])->toBe('poppins');
        expect($this->company->fresh()->resolvedSettings()['body_font'])->toBe('inter');
    });

    it('gestor não pode restaurar tipografia', function () {
        $this->actingAs($this->gestor)
            ->post(route('configuracoes.tipografia.reset'))
            ->assertForbidden();
    });

    it('admin pode salvar tipografia pela rota dedicada', function () {
        $this->actingAs($this->admin)
            ->post(route('configuracoes.tipografia'), [
                'heading_font' => 'jakarta',
                'body_font' => 'nunito',
            ])
            ->assertRedirect(route('configuracoes', ['tab' => 'tipografia']));

        $settings = $this->company->fresh()->settings;

        expect($settings['heading_font'])->toBe('jakarta');
        expect($settings['body_font'])->toBe('nunito');
    });

    it('admin pode salvar tipografia via POST com hidden fields', function () {
        $this->actingAs($this->admin)
            ->post(route('configuracoes.tipografia'), [
                'heading_font' => 'dm-serif',
                'body_font' => 'lato',
            ])
            ->assertRedirect(route('configuracoes', ['tab' => 'tipografia']))
            ->assertSessionHas('success');

        $settings = $this->company->fresh()->settings;

        expect($settings['heading_font'])->toBe('dm-serif');
        expect($settings['body_font'])->toBe('lato');
    });

    it('aplica fontes salvas no layout das páginas internas', function () {
        $this->company->update([
            'settings' => [
                'heading_font' => 'dm-serif',
                'body_font' => 'lato',
            ],
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('--sa-font-heading:', false)
            ->assertSee('DM Serif Display', false)
            ->assertSee('--sa-font-body:', false)
            ->assertSee('Lato', false)
            ->assertSee('family=DM+Serif+Display', false)
            ->assertSee('family=Lato', false);
    });

    it('persiste fontes e mantém após recarregar a página', function () {
        $this->actingAs($this->admin)
            ->post(route('configuracoes.tipografia'), [
                'heading_font' => 'montserrat',
                'body_font' => 'dm-sans',
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->get(route('configuracoes', ['tab' => 'tipografia']))
            ->assertOk()
            ->assertSee('name="heading_font"', false)
            ->assertSee('value="montserrat"', false)
            ->assertSee('montserrat', false);

        expect($this->company->fresh()->settings['heading_font'])->toBe('montserrat');
    });
});

describe('configuracoes empresa', function () {
    it('admin pode ver configurações da empresa', function () {
        $this->actingAs($this->admin)
            ->get(route('configuracoes.empresa'))
            ->assertOk()
            ->assertSee('Configurações da Empresa')
            ->assertSee($this->company->name);
    });

    it('admin pode atualizar nome e whatsapp', function () {
        $this->actingAs($this->admin)
            ->put(route('configuracoes.empresa.update'), [
                'name' => 'Barbearia Nova',
                'slug' => $this->company->slug,
                'whatsapp' => '(11) 88888-7777',
                'lgpd_consent' => '0',
                'tab' => 'dados',
            ])
            ->assertRedirect(route('configuracoes.empresa', ['tab' => 'dados']));

        expect($this->company->fresh()->name)->toBe('Barbearia Nova');
        expect($this->company->fresh()->whatsapp)->toBe('(11) 88888-7777');
    });

    it('admin pode ativar lgpd_consent', function () {
        $this->actingAs($this->admin)
            ->put(route('configuracoes.empresa.update'), [
                'name' => $this->company->name,
                'slug' => $this->company->slug,
                'lgpd_consent' => '1',
                'tab' => 'avancado',
            ])
            ->assertRedirect(route('configuracoes.empresa', ['tab' => 'avancado']));

        expect($this->company->fresh()->lgpd_consent)->toBeTrue();
    });

    it('gestor não pode atualizar empresa', function () {
        $this->actingAs($this->gestor)
            ->put(route('configuracoes.empresa.update'), [
                'name' => 'Hack',
                'slug' => 'hack',
                'whatsapp' => '(00) 00000-0000',
            ])
            ->assertForbidden();
    });

    it('nome é obrigatório', function () {
        $this->actingAs($this->admin)
            ->put(route('configuracoes.empresa.update'), [
                'name' => '',
                'slug' => $this->company->slug,
            ])
            ->assertSessionHasErrors('name');
    });

    it('isolamento: usuário sem empresa não acessa configurações', function () {
        $semEmpresa = User::factory()->create(['empresa_id' => null]);

        $this->actingAs($semEmpresa)
            ->get(route('configuracoes'))
            ->assertForbidden();
    });
});
