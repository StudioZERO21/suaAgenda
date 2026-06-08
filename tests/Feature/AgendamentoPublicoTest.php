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
    it('exibe página pública de agendamento pelo slug', function () {
        $this->get(route('agendar.show', $this->company->slug))
            ->assertOk()
            ->assertViewIs('public.agendar')
            ->assertSee($this->company->name)
            ->assertSee($this->servico->nome);
    });

    it('retorna 404 para empresa inexistente', function () {
        $this->get(route('agendar.show', 'slug-inexistente'))->assertNotFound();
    });

    it('retorna 404 para empresa inativa', function () {
        $this->company->update(['ativo' => false]);
        $this->get(route('agendar.show', $this->company->slug))->assertNotFound();
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
        $dataHora = now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s');

        $response = $this->post(route('agendar.store', $this->company->slug), [
            'servico_id' => $this->servico->id,
            'profissional_id' => $this->profissional->id,
            'data_hora' => $dataHora,
            'cliente_nome' => 'Maria Silva',
            'cliente_phone' => '11999999999',
            'cliente_email' => 'maria@exemplo.com',
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
});
