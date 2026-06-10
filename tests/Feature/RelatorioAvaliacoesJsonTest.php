<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Avaliacao;
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
        'name' => 'Barbearia RelAvJson', 'slug' => 'barbearia-relavjson',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001']);
});

function makeRelAvAg($self, int $diasAtras = 5): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->subDays($diasAtras),
        'duracao' => 30,
        'status' => 'finalizado',
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

function makeRelAvaliacao($self, Agendamento $ag, int $nota = 5, ?string $comentario = null): Avaliacao
{
    return Avaliacao::create([
        'company_id' => $self->company->id,
        'agendamento_id' => $ag->id,
        'nota' => $nota,
        'comentario' => $comentario,
    ]);
}

describe('relatorio_avaliacoes_json', function () {
    it('retorna estrutura correta', function () {
        $ag = makeRelAvAg($this);
        makeRelAvaliacao($this, $ag, 5, 'Ótimo!');

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.avaliacoes'))
            ->assertOk()
            ->json();

        expect($data[0])->toHaveKeys(['id', 'nota', 'comentario', 'data', 'cliente_nome', 'profissional_nome', 'servico_nome']);
    });

    it('retorna avaliações do período', function () {
        $ag1 = makeRelAvAg($this, 5);
        $ag2 = makeRelAvAg($this, 15);
        makeRelAvaliacao($this, $ag1);
        makeRelAvaliacao($this, $ag2);

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.avaliacoes', ['preset' => '7d']))
            ->json();

        expect(count($data))->toBe(1);
    });

    it('não expõe avaliações de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-relavjson', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $svcOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 30.0, 'cor' => '#000', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Z', 'phone' => '99999999999']);
        $agOutra = Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $svcOutra->id,
            'data_hora' => now()->subDays(2), 'duracao' => 30,
            'status' => 'finalizado', 'cancel_token' => Agendamento::generateCancelToken(),
        ]);
        Avaliacao::create(['company_id' => $outra->id, 'agendamento_id' => $agOutra->id, 'nota' => 5]);

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.avaliacoes'))
            ->json();

        expect($data)->toBeEmpty();
    });

    it('respeita limite customizado', function () {
        for ($i = 0; $i < 5; $i++) {
            $ag = makeRelAvAg($this, $i + 1);
            makeRelAvaliacao($this, $ag);
        }

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.avaliacoes', ['limite' => 2]))
            ->json();

        expect(count($data))->toBe(2);
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('relatorios.avaliacoes'))
            ->assertUnauthorized();
    });
});
