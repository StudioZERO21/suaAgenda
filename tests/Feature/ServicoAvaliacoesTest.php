<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Avaliacao;
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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia SAv', 'slug' => 'barbearia-sav',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'ativo' => true]);
});

function makeSavAg(string $companyId, string $clienteId, string $profId, string $servId): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'cliente_id' => $clienteId,
        'profissional_id' => $profId,
        'servico_id' => $servId,
        'data_hora' => now()->subDays(rand(1, 30))->toDateTimeString(),
        'duracao' => 30, 'valor' => 50.0, 'status' => 'finalizado',
    ]);
}

describe('servico_avaliacoes', function () {
    it('retorna estrutura correta quando sem avaliações', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.avaliacoes', $this->servico))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total_avaliacoes', 'nota_media', 'items']);
        expect($data['total_avaliacoes'])->toBe(0);
        expect($data['nota_media'])->toBeNull();
    });

    it('retorna avaliações do serviço com estrutura correta', function () {
        $ag = makeSavAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id);
        Avaliacao::create([
            'company_id' => $this->company->id,
            'agendamento_id' => $ag->id,
            'nota' => 5,
            'comentario' => 'Ótimo!',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.avaliacoes', $this->servico))
            ->assertOk()
            ->json();

        expect($data['total_avaliacoes'])->toBe(1);
        expect((float) $data['nota_media'])->toBe(5.0);
        expect($data['items'][0])->toHaveKeys(['id', 'nota', 'comentario', 'data', 'cliente_nome', 'profissional_nome']);
        expect($data['items'][0]['cliente_nome'])->toBe('João');
    });

    it('calcula nota média corretamente', function () {
        $ag1 = makeSavAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id);
        $ag2 = makeSavAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id);
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag1->id, 'nota' => 4]);
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag2->id, 'nota' => 2]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.avaliacoes', $this->servico))
            ->assertOk()
            ->json();

        expect((float) $data['nota_media'])->toBe(3.0);
    });

    it('não inclui avaliações de outro serviço', function () {
        $outroServico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Barba', 'preco' => 30.0, 'duracao_minutos' => 20, 'ativo' => true]);
        $ag = makeSavAg($this->company->id, $this->cliente->id, $this->prof->id, $outroServico->id);
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag->id, 'nota' => 5]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.avaliacoes', $this->servico))
            ->assertOk()
            ->json();

        expect($data['total_avaliacoes'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('servicos.avaliacoes', $this->servico))
            ->assertOk();
    });

    it('não pode ver avaliações de serviço de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-sav', 'plano' => 'trial', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 30.0, 'duracao_minutos' => 15, 'ativo' => true]);

        $this->actingAs($this->admin)
            ->getJson(route('servicos.avaliacoes', $servOutra))
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('servicos.avaliacoes', $this->servico))
            ->assertUnauthorized();
    });
});
