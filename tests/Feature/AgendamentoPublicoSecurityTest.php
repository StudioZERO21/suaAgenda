<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Company;
use App\Models\HorarioTrabalho;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Barbearia A', 'slug' => 'barbearia-a',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->outraEmpresa = Company::create([
        'name' => 'Barbearia B', 'slug' => 'barbearia-b',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id,
        'nome' => 'Corte', 'duracao_minutos' => 30,
        'preco' => 45.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Carlos', 'especialidade' => 'Barbeiro', 'ativo' => true,
    ]);

    $this->servicoOutraEmpresa = Servico::create([
        'company_id' => $this->outraEmpresa->id,
        'nome' => 'Corte B', 'duracao_minutos' => 60,
        'preco' => 90.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);

    $this->profissionalOutraEmpresa = Profissional::create([
        'company_id' => $this->outraEmpresa->id,
        'name' => 'Bruno', 'especialidade' => 'Barbeiro', 'ativo' => true,
    ]);

    $this->profissional->servicos()->attach($this->servico->id);
    HorarioTrabalho::create([
        'empresa_id' => $this->company->id,
        'profissional_id' => $this->profissional->id,
        'dia_semana' => (int) now()->addDay()->format('w'),
        'hora_inicio' => '08:00', 'hora_fim' => '18:00', 'ativo' => true,
    ]);
});

describe('agendamento_publico_seguranca', function () {
    it('rejeita serviço de outra empresa no agendamento público', function () {
        $this->post(route('agendar.store', $this->company->slug), [
            'servico_id' => $this->servicoOutraEmpresa->id,
            'profissional_id' => $this->profissional->id,
            'data_hora' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'cliente_nome' => 'Maria Silva',
            'cliente_phone' => '11999999999',
        ])->assertSessionHasErrors('servico_id');

        expect(Agendamento::count())->toBe(0);
    });

    it('rejeita profissional de outra empresa no agendamento público', function () {
        $this->post(route('agendar.store', $this->company->slug), [
            'servico_id' => $this->servico->id,
            'profissional_id' => $this->profissionalOutraEmpresa->id,
            'data_hora' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'cliente_nome' => 'Maria Silva',
            'cliente_phone' => '11999999999',
        ])->assertSessionHasErrors('profissional_id');

        expect(Agendamento::count())->toBe(0);
    });

    it('rejeita serviço inativo no agendamento público', function () {
        $this->servico->update(['ativo' => false]);

        $this->post(route('agendar.store', $this->company->slug), [
            'servico_id' => $this->servico->id,
            'profissional_id' => $this->profissional->id,
            'data_hora' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'cliente_nome' => 'Maria Silva',
            'cliente_phone' => '11999999999',
        ])->assertSessionHasErrors('servico_id');
    });

    it('rejeita profissional inativo no agendamento público', function () {
        $this->profissional->update(['ativo' => false]);

        $this->post(route('agendar.store', $this->company->slug), [
            'servico_id' => $this->servico->id,
            'profissional_id' => $this->profissional->id,
            'data_hora' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'cliente_nome' => 'Maria Silva',
            'cliente_phone' => '11999999999',
        ])->assertSessionHasErrors('profissional_id');
    });

    it('aceita agendamento legítimo da própria empresa', function () {
        $this->post(route('agendar.store', $this->company->slug), [
            'servico_id' => $this->servico->id,
            'profissional_id' => $this->profissional->id,
            'data_hora' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'cliente_nome' => 'Maria Silva',
            'cliente_phone' => '11999999999',
            'consent' => 1,
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        expect(Agendamento::where('company_id', $this->company->id)->count())->toBe(1);
    });
});
