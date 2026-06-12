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

    $this->company = Company::create([
        'name' => 'Barbearia ProfAg', 'slug' => 'barbearia-profag',
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

function makeProfAg($self, string $status = 'finalizado', int $diasAtras = 5): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->subDays($diasAtras),
        'duracao' => 30,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('profissional_agendamentos', function () {
    it('retorna estrutura correta', function () {
        makeProfAg($this);

        $this->actingAs($this->user)
            ->getJson(route('profissionais.agendamentos', $this->prof))
            ->assertOk()
            ->assertJsonStructure([
                'total', 'per_page', 'page',
                'data' => [['id', 'data_hora', 'cliente_nome', 'servico_nome', 'status', 'valor', 'duracao']],
            ]);
    });

    it('retorna todos os agendamentos do profissional', function () {
        makeProfAg($this, 'finalizado', 10);
        makeProfAg($this, 'cancelado', 5);
        makeProfAg($this, 'confirmado', 2);

        $data = $this->actingAs($this->user)
            ->getJson(route('profissionais.agendamentos', $this->prof))
            ->json();

        expect($data['total'])->toBe(3);
    });

    it('ordena por data_hora decrescente', function () {
        makeProfAg($this, 'finalizado', 10);
        makeProfAg($this, 'finalizado', 3);
        makeProfAg($this, 'finalizado', 7);

        $data = $this->actingAs($this->user)
            ->getJson(route('profissionais.agendamentos', $this->prof))
            ->json();

        $horarios = collect($data['data'])->pluck('data_hora')->all();
        $sorted = $horarios;
        rsort($sorted);
        expect($horarios)->toBe($sorted);
    });

    it('não acessa agendamentos de profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-profag', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->user)
            ->getJson(route('profissionais.agendamentos', $profOutra))
            ->assertForbidden();
    });

    it('pagina com per_page customizado', function () {
        for ($i = 0; $i < 5; $i++) {
            makeProfAg($this, 'finalizado', $i + 1);
        }

        $data = $this->actingAs($this->user)
            ->getJson(route('profissionais.agendamentos', [$this->prof, 'per_page' => 2]))
            ->json();

        expect($data['total'])->toBe(5);
        expect($data['per_page'])->toBe(2);
        expect(count($data['data']))->toBe(2);
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('profissionais.agendamentos', $this->prof))
            ->assertUnauthorized();
    });
});
