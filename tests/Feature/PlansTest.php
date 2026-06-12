<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Plan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor',        'guard_name' => 'web']);

    Plan::create([
        'slug' => 'starter',
        'nome' => 'Starter',
        'preco' => 0,
        'max_profissionais' => 2,
        'whatsapp_mensal' => 50,
        'sms_mensal' => 0,
        'max_whatsapp_overage' => 0,
        'features' => ['Até 2 profissionais', 'Agendamento online'],
        'color' => '#6b7280',
        'popular' => false,
        'ordem' => 1,
    ]);

    Plan::create([
        'slug' => 'profissional',
        'nome' => 'Profissional',
        'preco' => 97.90,
        'max_profissionais' => 10,
        'whatsapp_mensal' => 500,
        'sms_mensal' => 100,
        'max_whatsapp_overage' => 1000,
        'features' => ['Até 10 profissionais', 'WhatsApp 500/mês'],
        'color' => '#d4a574',
        'popular' => true,
        'ordem' => 3,
    ]);

    $this->company = Company::create([
        'name' => 'Empresa Teste',
        'slug' => 'empresa-teste',
        'plano' => 'trial',
        'plan_slug' => 'starter',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');
});

describe('plans model', function () {
    it('ordena planos por campo ordem', function () {
        $plans = Plan::ordered();

        expect($plans->first()->slug)->toBe('starter')
            ->and($plans->last()->slug)->toBe('profissional');
    });

    it('formata preço corretamente', function () {
        $plan = Plan::find('profissional');

        expect($plan->precoFormatado())->toBe('R$ 97,90');
    });

    it('starter retorna preço gratuito formatado', function () {
        $plan = Plan::find('starter');

        expect($plan->precoFormatado())->toBe('R$ 0,00');
    });

    it('detecta campo ilimitado com valor -1', function () {
        $plan = Plan::find('starter');
        $plan->max_profissionais = -1;

        expect($plan->ilimitado('max_profissionais'))->toBeTrue();
    });

    it('detecta campo não-ilimitado', function () {
        $plan = Plan::find('starter');

        expect($plan->ilimitado('max_profissionais'))->toBeFalse();
    });

    it('company pertence ao plan correto', function () {
        expect($this->company->plan->slug)->toBe('starter');
    });
});

describe('plans controller', function () {
    it('exibe página de planos para admin', function () {
        $this->actingAs($this->admin)
            ->get(route('planos.index'))
            ->assertOk()
            ->assertSee('Starter')
            ->assertSee('Profissional')
            ->assertSee('Planos & Assinatura');
    });

    it('gestor não acessa página de planos (sem cfg_plans)', function () {
        $this->actingAs($this->gestor)
            ->get(route('planos.index'))
            ->assertForbidden();
    });

    it('redireciona visitante para login', function () {
        $this->get(route('planos.index'))
            ->assertRedirect(route('login'));
    });

    it('admin pode atualizar plano da empresa', function () {
        $this->actingAs($this->admin)
            ->patch(route('planos.update'), ['plan_slug' => 'profissional'])
            ->assertRedirect();

        expect($this->company->fresh()->plan_slug)->toBe('profissional');
    });

    it('gestor não pode atualizar plano', function () {
        $this->actingAs($this->gestor)
            ->patch(route('planos.update'), ['plan_slug' => 'profissional'])
            ->assertForbidden();
    });

    it('slug inválido é rejeitado', function () {
        $this->actingAs($this->admin)
            ->patch(route('planos.update'), ['plan_slug' => 'inexistente'])
            ->assertSessionHasErrors('plan_slug');
    });
});
