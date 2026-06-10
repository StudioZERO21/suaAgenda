<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia ProfRel', 'slug' => 'barbearia-profrel',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->profA = Profissional::create(['company_id' => $this->company->id, 'name' => 'Ana', 'ativo' => true]);
    $this->profB = Profissional::create(['company_id' => $this->company->id, 'name' => 'Bruno', 'ativo' => true]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);

    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001']);
});

function makeProfRelAg($self, Profissional $prof, string $status = 'finalizado', float $valor = 100.0): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->subDays(5),
        'duracao' => 30,
        'status' => $status,
        'valor' => $valor,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('relatorio_profissionais', function () {
    it('retorna estrutura correta com todos os profissionais', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.profissionais'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(2);
        expect($data[0])->toHaveKeys(['id', 'name', 'total', 'finalizados', 'receita_total', 'taxa_conclusao', 'nota_media']);
    });

    it('calcula métricas corretamente por profissional', function () {
        makeProfRelAg($this, $this->profA, 'finalizado', 150.0);
        makeProfRelAg($this, $this->profA, 'finalizado', 50.0);
        makeProfRelAg($this, $this->profA, 'cancelado');
        makeProfRelAg($this, $this->profB, 'finalizado', 80.0);

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.profissionais'))
            ->json();

        $ana = collect($data)->firstWhere('name', 'Ana');
        $bruno = collect($data)->firstWhere('name', 'Bruno');

        expect($ana['total'])->toBe(3);
        expect($ana['finalizados'])->toBe(2);
        expect((float) $ana['receita_total'])->toBe(200.0);

        expect($bruno['total'])->toBe(1);
        expect($bruno['finalizados'])->toBe(1);
        expect((float) $bruno['receita_total'])->toBe(80.0);
    });

    it('ordena por receita decrescente', function () {
        makeProfRelAg($this, $this->profA, 'finalizado', 50.0);
        makeProfRelAg($this, $this->profB, 'finalizado', 300.0);

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.profissionais'))
            ->json();

        expect($data[0]['name'])->toBe('Bruno');
        expect($data[1]['name'])->toBe('Ana');
    });

    it('taxa_conclusao calculada corretamente', function () {
        makeProfRelAg($this, $this->profA, 'finalizado');
        makeProfRelAg($this, $this->profA, 'cancelado');

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.profissionais'))
            ->json();

        $ana = collect($data)->firstWhere('name', 'Ana');
        expect((float) $ana['taxa_conclusao'])->toBe(50.0);
    });

    it('não expõe profissionais de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-profrel', 'plano' => 'trial', 'ativo' => true]);
        Profissional::create(['company_id' => $outra->id, 'name' => 'Intruso', 'ativo' => true]);

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.profissionais'))
            ->json();

        $nomes = array_column($data, 'name');
        expect($nomes)->not->toContain('Intruso');
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('relatorios.profissionais'))
            ->assertUnauthorized();
    });
});
