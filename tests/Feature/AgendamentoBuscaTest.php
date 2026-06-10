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
    $this->company = Company::create([
        'name' => 'Barbearia Busca', 'slug' => 'barbearia-busca',
        'plano' => 'trial', 'ativo' => true,
    ]);

    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte Premium',
        'duracao_minutos' => 30, 'preco' => 60.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->clienteAna = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana Costa', 'phone' => '11999990001']);
    $this->clienteBruno = Cliente::create(['company_id' => $this->company->id, 'name' => 'Bruno Lima', 'phone' => '11999990002']);

    $this->ag1 = Agendamento::create([
        'company_id' => $this->company->id, 'cliente_id' => $this->clienteAna->id,
        'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
        'data_hora' => now()->addDay()->setTime(10, 0), 'duracao' => 30,
        'status' => 'pendente', 'cancel_token' => Agendamento::generateCancelToken(),
    ]);
    $this->ag2 = Agendamento::create([
        'company_id' => $this->company->id, 'cliente_id' => $this->clienteBruno->id,
        'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
        'data_hora' => now()->addDay()->setTime(11, 0), 'duracao' => 30,
        'status' => 'confirmado', 'cancel_token' => Agendamento::generateCancelToken(),
    ]);
});

describe('agendamento_busca', function () {
    it('busca por nome de cliente retorna somente correspondentes', function () {
        $response = $this->actingAs($this->user)
            ->get(route('agendamentos.index', ['q' => 'Ana', 'status' => 'pendente']));

        $response->assertOk();
        $ags = $response->getOriginalContent()->getData()['agendamentos'];
        expect($ags->total())->toBe(1);
        expect($ags->first()->cliente->name)->toBe('Ana Costa');
    });

    it('busca por nome parcial funciona', function () {
        $response = $this->actingAs($this->user)
            ->get(route('agendamentos.index', ['q' => 'bru', 'status' => 'confirmado']));

        $ags = $response->getOriginalContent()->getData()['agendamentos'];
        expect($ags->total())->toBe(1);
        expect($ags->first()->cliente->name)->toBe('Bruno Lima');
    });

    it('filtro por servico_id retorna apenas agendamentos daquele serviço', function () {
        $outroServico = Servico::create([
            'company_id' => $this->company->id, 'nome' => 'Barba',
            'duracao_minutos' => 20, 'preco' => 40.00, 'cor' => '#d4a574', 'ativo' => true,
        ]);

        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->clienteAna->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $outroServico->id,
            'data_hora' => now()->addDay()->setTime(14, 0), 'duracao' => 20,
            'status' => 'pendente', 'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('agendamentos.index', ['servico_id' => $outroServico->id, 'status' => 'pendente']));

        $ags = $response->getOriginalContent()->getData()['agendamentos'];
        expect($ags->total())->toBe(1);
        expect($ags->first()->servico->nome)->toBe('Barba');
    });

    it('view inclui campo de busca por cliente', function () {
        $this->actingAs($this->user)
            ->get(route('agendamentos.index'))
            ->assertOk()
            ->assertSee('name="q"', false)
            ->assertSee('Buscar por nome', false);
    });

    it('vitrine exibe nota média real quando existem avaliações', function () {
        $ag = $this->ag1->update(['status' => 'finalizado']);
        $agFresh = $this->ag1->fresh();
        Avaliacao::create([
            'company_id' => $this->company->id,
            'agendamento_id' => $agFresh->id,
            'nota' => 5,
            'comentario' => 'Perfeito!',
        ]);

        $this->get(route('vitrine.show', $this->company->slug))
            ->assertOk()
            ->assertSee('5,0★');
    });
});
