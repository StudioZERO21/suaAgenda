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
        'name' => 'Barbearia Prof', 'slug' => 'barbearia-prof',
        'plano' => 'trial', 'ativo' => true,
    ]);

    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos Silva', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001']);

    $this->ag = Agendamento::create([
        'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
        'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
        'data_hora' => now()->setTime(10, 0), 'duracao' => 30,
        'valor' => 50.00, 'status' => 'finalizado',
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
});

describe('profissional_show', function () {
    it('exibe perfil do profissional com stats', function () {
        $this->actingAs($this->user)
            ->get(route('profissionais.show', $this->prof))
            ->assertOk()
            ->assertSee('Carlos Silva')
            ->assertSee('Agend. este mês')
            ->assertSee('Taxa Conclusão');
    });

    it('totalMes conta apenas agendamentos do mês corrente', function () {
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->subYear()->setTime(10, 0), 'duracao' => 30,
            'status' => 'finalizado', 'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->user)
            ->get(route('profissionais.show', $this->prof))
            ->getOriginalContent()->getData();

        expect($data['totalMes'])->toBe(1);
    });

    it('receitaMes soma apenas finalizados no mês', function () {
        $data = $this->actingAs($this->user)
            ->get(route('profissionais.show', $this->prof))
            ->getOriginalContent()->getData();

        expect($data['receitaMes'])->toBe(50.0);
    });

    it('notaMedia é calculada corretamente', function () {
        Avaliacao::create([
            'company_id' => $this->company->id,
            'agendamento_id' => $this->ag->id,
            'nota' => 5,
        ]);

        $data = $this->actingAs($this->user)
            ->get(route('profissionais.show', $this->prof))
            ->getOriginalContent()->getData();

        expect((float) $data['notaMedia'])->toBe(5.0);
    });

    it('notaMedia é null quando sem avaliações', function () {
        $data = $this->actingAs($this->user)
            ->get(route('profissionais.show', $this->prof))
            ->getOriginalContent()->getData();

        expect($data['notaMedia'])->toBeNull();
    });
});
