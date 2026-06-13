<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\HorarioTrabalho;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Barbearia Pública', 'slug' => 'barbearia-publica',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id,
        'nome' => 'Corte',
        'duracao_minutos' => 30,
        'preco' => 45.00,
        'cor' => '#1a1a1a',
        'ativo' => true,
    ]);

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Carlos',
        'especialidade' => 'Barbeiro',
        'ativo' => true,
    ]);

    $this->profissional->servicos()->attach($this->servico->id);
});

describe('agendamento_publico', function () {
    it('redireciona /agendar para a vitrine com o modal aberto', function () {
        $this->get(route('agendar.show', $this->company->slug))
            ->assertRedirect(route('vitrine.show', ['slug' => $this->company->slug, 'book' => 1]));
    });

    it('repassa pré-seleção de serviço/profissional no redirect', function () {
        $this->get(route('agendar.show', $this->company->slug).'?servico_id='.$this->servico->id)
            ->assertRedirect(route('vitrine.show', ['slug' => $this->company->slug, 'book' => 1, 'servico_id' => $this->servico->id]));
    });

    it('vitrine retorna 404 para empresa inexistente', function () {
        $this->get(route('vitrine.show', 'slug-inexistente'))->assertNotFound();
    });

    it('vitrine retorna 404 para empresa inativa', function () {
        $this->company->update(['ativo' => false]);
        $this->get(route('vitrine.show', $this->company->slug))->assertNotFound();
    });

    it('slots retorna vazio sem horário configurado', function () {
        $data = now()->addDay()->format('Y-m-d');
        $response = $this->getJson(route('agendar.slots', $this->company->slug).'?'.http_build_query([
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'data' => $data,
        ]));
        $response->assertOk();
        expect($response->json())->toBeEmpty();
    });

    it('slots retorna horários quando há configuração', function () {
        HorarioTrabalho::create([
            'empresa_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'dia_semana' => (int) now()->addDay()->format('w'),
            'hora_inicio' => '08:00',
            'hora_fim' => '12:00',
            'ativo' => true,
        ]);

        $data = now()->addDay()->format('Y-m-d');
        $response = $this->getJson(route('agendar.slots', $this->company->slug).'?'.http_build_query([
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'data' => $data,
        ]));

        $response->assertOk();
        expect($response->json())->not->toBeEmpty();
        expect($response->json()[0])->toHaveKey('hora');
    });

    it('cria agendamento público com dados do cliente', function () {
        HorarioTrabalho::create([
            'empresa_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'dia_semana' => (int) now()->addDay()->format('w'),
            'hora_inicio' => '08:00', 'hora_fim' => '18:00', 'ativo' => true,
        ]);
        $dataHora = now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s');

        $response = $this->post(route('agendar.store', $this->company->slug), [
            'servico_id' => $this->servico->id,
            'profissional_id' => $this->profissional->id,
            'data_hora' => $dataHora,
            'cliente_nome' => 'Maria Silva',
            'cliente_phone' => '11999999999',
            'cliente_email' => 'maria@exemplo.com',
            'consent' => 1,
        ]);

        $response->assertRedirect();

        expect(Agendamento::where('company_id', $this->company->id)->count())->toBe(1);
        expect(Cliente::where('phone', '11999999999')->count())->toBe(1);
    });

    it('não cria agendamento sem profissional', function () {
        $this->post(route('agendar.store', $this->company->slug), [
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay()->format('Y-m-d H:i:s'),
            'cliente_nome' => 'Maria',
            'cliente_phone' => '11999999999',
        ])->assertSessionHasErrors('profissional_id');
    });

    it('disponibilidade retorna array vazio sem horário configurado', function () {
        $data = now()->addDay()->format('Y-m-d');
        $response = $this->getJson(route('vitrine.disponibilidade', $this->company->slug).'?'.http_build_query([
            'servico_id' => $this->servico->id,
            'data' => $data,
        ]));
        $response->assertOk();
        $profissionais = $response->json();
        expect($profissionais)->toBeArray();
        $slots = collect($profissionais)->firstWhere('profissional.id', $this->profissional->id);
        expect($slots['slots'])->toBeEmpty();
    });

    it('disponibilidade retorna slots quando há horário configurado', function () {
        $diaSemana = (int) now()->addDay()->format('w');
        HorarioTrabalho::create([
            'empresa_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'dia_semana' => $diaSemana,
            'hora_inicio' => '08:00',
            'hora_fim' => '12:00',
            'ativo' => true,
        ]);

        $data = now()->addDay()->format('Y-m-d');
        $response = $this->getJson(route('vitrine.disponibilidade', $this->company->slug).'?'.http_build_query([
            'servico_id' => $this->servico->id,
            'data' => $data,
        ]));
        $response->assertOk();
        $profissionais = $response->json();
        $row = collect($profissionais)->firstWhere('profissional.id', $this->profissional->id);
        expect($row)->not->toBeNull();
        expect($row['slots'])->not->toBeEmpty();
        expect($row['slots'][0])->toHaveKeys(['hora', 'disponivel']);
        expect($row['slots'][0]['disponivel'])->toBeTrue();
    });

    it('disponibilidade marca slot como indisponível quando ocupado', function () {
        $diaSemana = (int) now()->addDay()->format('w');
        HorarioTrabalho::create([
            'empresa_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'dia_semana' => $diaSemana,
            'hora_inicio' => '08:00',
            'hora_fim' => '10:00',
            'ativo' => true,
        ]);

        $cliente = Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'Test',
            'phone' => '11999990001',
        ]);

        $dataHora = now()->addDay()->setTime(8, 0)->format('Y-m-d H:i:s');
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'cliente_id' => $cliente->id,
            'data_hora' => $dataHora,
            'duracao' => 30,
            'status' => 'confirmado',
        ]);

        $data = now()->addDay()->format('Y-m-d');
        $response = $this->getJson(route('vitrine.disponibilidade', $this->company->slug).'?'.http_build_query([
            'servico_id' => $this->servico->id,
            'data' => $data,
        ]));
        $response->assertOk();
        $row = collect($response->json())->firstWhere('profissional.id', $this->profissional->id);
        $slot800 = collect($row['slots'])->firstWhere('hora', '08:00');
        expect($slot800['disponivel'])->toBeFalse();
    });

    it('disponibilidade valida data no passado', function () {
        $this->getJson(route('vitrine.disponibilidade', $this->company->slug).'?'.http_build_query([
            'servico_id' => $this->servico->id,
            'data' => now()->subDay()->format('Y-m-d'),
        ]))->assertUnprocessable();
    });

    it('confirm_required=false (padrão) cria agendamento com status confirmado automaticamente', function () {
        HorarioTrabalho::create([
            'empresa_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'dia_semana' => (int) now()->addDay()->format('w'),
            'hora_inicio' => '08:00', 'hora_fim' => '18:00', 'ativo' => true,
        ]);
        $this->post(route('agendar.store', $this->company->slug), [
            'servico_id' => $this->servico->id,
            'profissional_id' => $this->profissional->id,
            'data_hora' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'cliente_nome' => 'Auto Confirmado',
            'cliente_phone' => '11988888888',
            'cliente_email' => 'auto@teste.com',
            'consent' => 1,
        ]);

        $ag = Agendamento::where('company_id', $this->company->id)->first();
        expect($ag->status)->toBe(Agendamento::STATUS_CONFIRMADO);
    });

    it('confirm_required=true cria agendamento pendente', function () {
        $settings = $this->company->settings ?? [];
        $settings['advanced']['confirm_required'] = true;
        $this->company->update(['settings' => $settings]);

        HorarioTrabalho::create([
            'empresa_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'dia_semana' => (int) now()->addDay()->format('w'),
            'hora_inicio' => '08:00', 'hora_fim' => '18:00', 'ativo' => true,
        ]);
        $this->post(route('agendar.store', $this->company->slug), [
            'servico_id' => $this->servico->id,
            'profissional_id' => $this->profissional->id,
            'data_hora' => now()->addDay()->setTime(11, 0)->format('Y-m-d H:i:s'),
            'cliente_nome' => 'Pendente',
            'cliente_phone' => '11977777777',
            'consent' => 1,
        ]);

        $ag = Agendamento::where('company_id', $this->company->id)->first();
        expect($ag->status)->toBe(Agendamento::STATUS_PENDENTE);
    });
});
