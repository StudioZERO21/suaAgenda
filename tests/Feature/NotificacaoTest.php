<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Notificacao;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Barbearia Notif', 'slug' => 'barbearia-notif',
        'plano' => 'trial', 'ativo' => true,
    ]);

    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Carlos',
        'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id,
        'nome' => 'Corte',
        'duracao_minutos' => 30,
        'preco' => 45.00,
        'cor' => '#1a1a1a',
        'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'João',
        'phone' => '11999990001',
    ]);
});

function makeAg(array $attrs = []): Agendamento
{
    return Agendamento::create(array_merge([
        'company_id' => test()->company->id,
        'profissional_id' => test()->profissional->id,
        'servico_id' => test()->servico->id,
        'cliente_id' => test()->cliente->id,
        'data_hora' => now()->addDay()->setTime(10, 0),
        'duracao' => 30,
        'valor' => 45.00,
        'status' => Agendamento::STATUS_PENDENTE,
        'cancel_token' => Agendamento::generateCancelToken(),
    ], $attrs));
}

describe('notificacoes', function () {
    it('cria notificação ao criar agendamento', function () {
        makeAg();
        expect(Notificacao::where('company_id', $this->company->id)->where('tipo', 'novo_agendamento')->count())->toBe(1);
    });

    it('cria notificação ao cancelar agendamento', function () {
        $ag = makeAg();
        $ag->update(['status' => Agendamento::STATUS_CANCELADO]);
        expect(Notificacao::where('company_id', $this->company->id)->where('tipo', 'cancelamento')->count())->toBe(1);
    });

    it('não cria notificação de cancelamento se status não mudou', function () {
        $ag = makeAg();
        $ag->update(['observacao' => 'observação qualquer']);
        expect(Notificacao::where('tipo', 'cancelamento')->count())->toBe(0);
    });

    it('API index retorna notificações da empresa', function () {
        Notificacao::create([
            'company_id' => $this->company->id,
            'tipo' => 'novo_agendamento',
            'titulo' => 'Novo',
            'mensagem' => 'Teste',
        ]);

        $this->actingAs($this->user)
            ->getJson(route('notificacoes.index'))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.tipo', 'novo_agendamento');
    });

    it('marca notificação como lida', function () {
        $n = Notificacao::create([
            'company_id' => $this->company->id,
            'tipo' => 'novo_agendamento',
            'titulo' => 'Novo',
            'mensagem' => 'Teste',
        ]);

        $this->actingAs($this->user)
            ->patchJson(route('notificacoes.lida', $n->id))
            ->assertOk();

        expect($n->fresh()->read_at)->not->toBeNull();
    });

    it('marca todas as notificações como lidas', function () {
        Notificacao::create(['company_id' => $this->company->id, 'tipo' => 'novo_agendamento', 'titulo' => 'A', 'mensagem' => 'x']);
        Notificacao::create(['company_id' => $this->company->id, 'tipo' => 'novo_agendamento', 'titulo' => 'B', 'mensagem' => 'x']);

        $this->actingAs($this->user)
            ->patchJson(route('notificacoes.todas-lidas'))
            ->assertOk();

        expect(Notificacao::where('company_id', $this->company->id)->whereNull('read_at')->count())->toBe(0);
    });

    it('não permite acessar notificações de outra empresa', function () {
        $outraCompany = Company::create(['name' => 'Outra', 'slug' => 'outra', 'plano' => 'trial', 'ativo' => true]);
        $n = Notificacao::create([
            'company_id' => $outraCompany->id,
            'tipo' => 'novo_agendamento',
            'titulo' => 'X',
            'mensagem' => 'x',
        ]);

        $this->actingAs($this->user)
            ->patchJson(route('notificacoes.lida', $n->id))
            ->assertForbidden();
    });
});
