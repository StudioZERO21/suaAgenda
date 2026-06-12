<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Comissoes', 'slug' => 'barbearia-com',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Carlos',
        'ativo' => true,
        'comissao_pct' => 20.0,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id,
        'nome' => 'Corte',
        'duracao_minutos' => 30,
        'preco' => 50.00,
        'cor' => '#1a1a1a',
        'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'João',
        'phone' => '11999990001',
    ]);
});

function makeComAg($self, Profissional $prof, string $status = 'finalizado', float $valor = 100.0): void
{
    Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->subDays(3)->setTime(10, 0),
        'duracao' => 30,
        'status' => $status,
        'valor' => $valor,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('comissoes_export', function () {
    it('admin pode exportar CSV de comissões', function () {
        makeComAg($this, $this->prof);

        $response = $this->actingAs($this->user)
            ->get(route('relatorios.comissoes.exportar'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    });

    it('CSV contém cabeçalhos corretos', function () {
        $response = $this->actingAs($this->user)
            ->get(route('relatorios.comissoes.exportar'));

        $content = $response->streamedContent();
        expect($content)->toContain('Profissional')
            ->and($content)->toContain('% Comissão')
            ->and($content)->toContain('Valor Comissão');
    });

    it('calcula valor de comissão corretamente', function () {
        makeComAg($this, $this->prof, 'finalizado', 200.0);
        makeComAg($this, $this->prof, 'finalizado', 100.0);

        $content = $this->actingAs($this->user)
            ->get(route('relatorios.comissoes.exportar'))
            ->streamedContent();

        // 20% de R$300 = R$60,00
        expect($content)->toContain('Carlos')
            ->and($content)->toContain('60,00');
    });

    it('não conta agendamentos não finalizados na comissão', function () {
        makeComAg($this, $this->prof, 'finalizado', 100.0);
        makeComAg($this, $this->prof, 'pendente', 100.0);

        $content = $this->actingAs($this->user)
            ->get(route('relatorios.comissoes.exportar'))
            ->streamedContent();

        // apenas 20% de R$100 = R$20,00, não R$40,00
        expect($content)->toContain('20,00');
        expect($content)->not->toContain('40,00');
    });

    it('inclui profissionais sem comissão definida (0%)', function () {
        $profSemComissao = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'Pedro Sem Comissao',
            'ativo' => true,
        ]);
        makeComAg($this, $profSemComissao, 'finalizado', 200.0);

        $content = $this->actingAs($this->user)
            ->get(route('relatorios.comissoes.exportar'))
            ->streamedContent();

        expect($content)->toContain('Pedro Sem Comissao')
            ->and($content)->toContain('0,0');
    });

    it('não expõe profissionais de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-com', 'plano' => 'trial', 'ativo' => true]);
        Profissional::create([
            'company_id' => $outra->id,
            'name' => 'Intruso',
            'ativo' => true,
            'comissao_pct' => 30.0,
        ]);

        $content = $this->actingAs($this->user)
            ->get(route('relatorios.comissoes.exportar'))
            ->streamedContent();

        expect($content)->not->toContain('Intruso');
    });

    it('unauthenticated é redirecionado para login', function () {
        $this->get(route('relatorios.comissoes.exportar'))
            ->assertRedirect(route('login'));
    });
});
