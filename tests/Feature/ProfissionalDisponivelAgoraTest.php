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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia DispAgora', 'slug' => 'barbearia-dispagora',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof1 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof Livre', 'ativo' => true]);
    $this->prof2 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof Ocupado', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte DA', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente DA', 'lgpd_consent' => true]);
});

function makeAgDispAgora(string $companyId, string $profId, string $clienteId, string $servicoId, string $status): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subMinutes(10),
        'duracao' => 30,
        'valor' => 50,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('profissional_disponivel_agora', function () {
    it('retorna todos disponíveis sem agendamentos ativos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.disponivel-agora'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['agora', 'janela_minutos', 'total', 'disponiveis', 'ocupados', 'items']);
        expect($data['total'])->toBe(2);
        expect($data['disponiveis'])->toBe(2);
        expect($data['ocupados'])->toBe(0);
        expect($data['items'][0])->toHaveKeys(['profissional_id', 'profissional_nome', 'disponivel', 'em_atendimento', 'proximo_em_breve']);
    });

    it('marca profissional em atendimento como ocupado', function () {
        makeAgDispAgora($this->company->id, $this->prof2->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_EM_ATENDIMENTO);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.disponivel-agora'))
            ->assertOk()
            ->json();

        expect($data['disponiveis'])->toBe(1);
        expect($data['ocupados'])->toBe(1);

        $prof2Item = collect($data['items'])->firstWhere('profissional_nome', 'Prof Ocupado');
        expect($prof2Item['disponivel'])->toBeFalse();
        expect($prof2Item['em_atendimento'])->toBeTrue();

        $prof1Item = collect($data['items'])->firstWhere('profissional_nome', 'Prof Livre');
        expect($prof1Item['disponivel'])->toBeTrue();
    });

    it('exclui profissionais inativos', function () {
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Inativo', 'ativo' => false]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.disponivel-agora'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(2);
    });

    it('ignora profissionais de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra DA', 'slug' => 'outra-da', 'plano' => 'trial', 'ativo' => true]);
        Profissional::create(['company_id' => $outra->id, 'name' => 'Outro', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.disponivel-agora'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(2);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.disponivel-agora'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('profissionais.disponivel-agora'))
            ->assertUnauthorized();
    });
});
