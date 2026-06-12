<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\CompanyRegra;
use App\Models\Profissional;
use App\Models\RegraCatalogo;
use App\Models\Role;
use App\Models\Servico;
use App\Models\User;
use Database\Seeders\RegraCatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ativarRegra(Company $company, string $codigo, array $params = []): void
{
    $catalogo = RegraCatalogo::where('codigo', $codigo)->firstOrFail();

    CompanyRegra::updateOrCreate(
        ['company_id' => $company->id, 'regra_catalogo_id' => $catalogo->id],
        ['ativo' => true, 'params' => $params],
    );
}

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web', 'company_id' => null]);

    $this->seed(RegraCatalogoSeeder::class);

    $this->company = Company::create(['name' => 'Barbearia Regras', 'slug' => 'barbearia-regras', 'plano' => 'trial', 'ativo' => true]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50, 'cor' => '#111', 'ativo' => true,
    ]);

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id, 'name' => 'Maria', 'phone' => '11999990000',
    ]);
});

function novoAgendamento(array $attrs = []): Agendamento
{
    return Agendamento::create(array_merge([
        'company_id' => test()->company->id,
        'cliente_id' => test()->cliente->id,
        'profissional_id' => test()->profissional->id,
        'servico_id' => test()->servico->id,
        'data_hora' => now()->addHours(10),
        'duracao' => 30,
        'valor' => 50,
        'status' => Agendamento::STATUS_CONFIRMADO,
        'cancel_token' => Agendamento::generateCancelToken(),
    ], $attrs));
}

describe('regra_cancelamento', function () {
    it('sem regra ativa o cancelamento futuro continua permitido', function () {
        $ag = novoAgendamento(['data_hora' => now()->addHours(2)]);

        $this->post(route('agendamento.cancelar', $ag->cancel_token))
            ->assertRedirect()
            ->assertSessionHas('cancelado');

        expect($ag->fresh()->status)->toBe(Agendamento::STATUS_CANCELADO);
    });

    it('bloqueia cancelamento dentro do prazo de antecedência', function () {
        ativarRegra($this->company, 'cancelamento_antecedencia', ['horas_min' => 24]);

        $ag = novoAgendamento(['data_hora' => now()->addHours(10)]);

        $this->post(route('agendamento.cancelar', $ag->cancel_token))
            ->assertRedirect()
            ->assertSessionHas('erro');

        expect($ag->fresh()->status)->toBe(Agendamento::STATUS_CONFIRMADO);
    });

    it('permite cancelamento fora do prazo de antecedência', function () {
        ativarRegra($this->company, 'cancelamento_antecedencia', ['horas_min' => 24]);

        $ag = novoAgendamento(['data_hora' => now()->addHours(48)]);

        $this->post(route('agendamento.cancelar', $ag->cancel_token))
            ->assertRedirect()
            ->assertSessionHas('cancelado');

        expect($ag->fresh()->status)->toBe(Agendamento::STATUS_CANCELADO);
    });

    it('informa que o sinal não é reembolsável após o prazo', function () {
        ativarRegra($this->company, 'cancelamento_antecedencia', ['horas_min' => 24]);
        ativarRegra($this->company, 'sinal', ['percentual' => 30, 'reembolsavel' => false]);

        $ag = novoAgendamento(['data_hora' => now()->addHours(5)]);

        $resposta = $this->post(route('agendamento.cancelar', $ag->cancel_token));

        expect(session('erro'))->toContain('sinal pago não é reembolsável');
    });

    it('página do agendamento exibe a política configurada', function () {
        ativarRegra($this->company, 'cancelamento_antecedencia', ['horas_min' => 24]);
        ativarRegra($this->company, 'sinal', ['percentual' => 30, 'reembolsavel' => true]);

        $ag = novoAgendamento(['data_hora' => now()->addHours(48)]);

        $this->get(route('agendamento.meu', $ag->cancel_token))
            ->assertOk()
            ->assertSee('Cancelamento permitido até 24h')
            ->assertSee('sinal de 30%');
    });
});

describe('regra_no_show', function () {
    it('bloqueia agendamento online de cliente com faltas recorrentes', function () {
        ativarRegra($this->company, 'no_show', ['bloquear_apos' => 2]);

        novoAgendamento(['status' => Agendamento::STATUS_NO_SHOW, 'data_hora' => now()->subDays(5)]);
        novoAgendamento(['status' => Agendamento::STATUS_NO_SHOW, 'data_hora' => now()->subDays(3)]);

        $this->post(route('agendar.store', $this->company->slug), [
            'servico_id' => $this->servico->id,
            'profissional_id' => $this->profissional->id,
            'data_hora' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'cliente_nome' => 'Maria',
            'cliente_phone' => '11999990000',
        ])->assertSessionHasErrors('cliente_phone');
    });

    it('sem a regra ativa o cliente faltante ainda agenda', function () {
        novoAgendamento(['status' => Agendamento::STATUS_NO_SHOW, 'data_hora' => now()->subDays(5)]);
        novoAgendamento(['status' => Agendamento::STATUS_NO_SHOW, 'data_hora' => now()->subDays(3)]);

        $this->post(route('agendar.store', $this->company->slug), [
            'servico_id' => $this->servico->id,
            'profissional_id' => $this->profissional->id,
            'data_hora' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'cliente_nome' => 'Maria',
            'cliente_phone' => '11999990000',
        ])->assertSessionDoesntHaveErrors()->assertRedirect();
    });

    it('admin marca agendamento como no_show', function () {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@noshow.test',
            'password' => bcrypt('secret123'), 'empresa_id' => $this->company->id, 'ativo' => true,
        ]);
        $admin->assignRole('admin_empresa');

        $ag = novoAgendamento(['data_hora' => now()->subHours(2)]);

        $this->actingAs($admin)
            ->patchJson(route('agendamentos.updateStatus', $ag), ['status' => 'no_show'])
            ->assertOk();

        expect($ag->fresh()->status)->toBe(Agendamento::STATUS_NO_SHOW);
    });

    it('no_show não conta como agendamento ativo nem bloqueia slot', function () {
        $ag = novoAgendamento(['status' => Agendamento::STATUS_NO_SHOW, 'data_hora' => today()->setTime(10, 0)]);

        expect(Agendamento::ativo()->count())->toBe(0);
    });
});
