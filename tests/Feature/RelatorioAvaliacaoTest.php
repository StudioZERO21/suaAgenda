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
    $this->company = Company::create([
        'name' => 'Barbearia Rel', 'slug' => 'barbearia-rel',
        'plano' => 'trial', 'ativo' => true,
    ]);

    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'phone' => '11999990001']);
});

function makeRelAg(string $status = 'finalizado', float $valor = 50.0): Agendamento
{
    return Agendamento::create([
        'company_id' => test()->company->id,
        'profissional_id' => test()->prof->id,
        'servico_id' => test()->servico->id,
        'cliente_id' => test()->cliente->id,
        'data_hora' => now()->subDay()->setTime(10, 0),
        'duracao' => 30,
        'valor' => $valor,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('relatorio_avaliacoes', function () {
    it('exibe aba avaliações com nota média', function () {
        $ag = makeRelAg();
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag->id, 'nota' => 5, 'comentario' => 'Ótimo!']);

        $this->actingAs($this->user)
            ->get(route('relatorios'))
            ->assertOk()
            ->assertSee('Avaliações');
    });

    it('calcula nota média geral corretamente', function () {
        $ag1 = makeRelAg();
        $ag2 = makeRelAg();
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag1->id, 'nota' => 5]);
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag2->id, 'nota' => 3]);

        $response = $this->actingAs($this->user)->get(route('relatorios'));
        $response->assertOk();

        $data = $response->getOriginalContent()->getData();
        expect($data['notaMediaGeral'])->toBe(4.0);
        expect($data['totalAvaliacoes'])->toBe(2);
    });

    it('calcula distribuição de notas corretamente', function () {
        $ag1 = makeRelAg();
        $ag2 = makeRelAg();
        $ag3 = makeRelAg();
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag1->id, 'nota' => 5]);
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag2->id, 'nota' => 5]);
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag3->id, 'nota' => 3]);

        $data = $this->actingAs($this->user)->get(route('relatorios'))->getOriginalContent()->getData();
        expect($data['distribuicaoNotas']['5'])->toBe(2);
        expect($data['distribuicaoNotas']['3'])->toBe(1);
        expect($data['distribuicaoNotas']['1'])->toBe(0);
    });

    it('calcula NPS corretamente', function () {
        $ag1 = makeRelAg();
        $ag2 = makeRelAg();
        $ag3 = makeRelAg();
        $ag4 = makeRelAg();
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag1->id, 'nota' => 5]);
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag2->id, 'nota' => 4]);
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag3->id, 'nota' => 3]);
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag4->id, 'nota' => 2]);

        $data = $this->actingAs($this->user)->get(route('relatorios'))->getOriginalContent()->getData();
        expect($data['nps'])->toBe(50); // 2 de 4 com nota >= 4
    });

    it('retorna comentários recentes apenas com texto', function () {
        $ag1 = makeRelAg();
        $ag2 = makeRelAg();
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag1->id, 'nota' => 5, 'comentario' => 'Excelente!']);
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag2->id, 'nota' => 4, 'comentario' => null]);

        $data = $this->actingAs($this->user)->get(route('relatorios'))->getOriginalContent()->getData();
        expect($data['comentariosRecentes'])->toHaveCount(1);
        expect($data['comentariosRecentes'][0]['comentario'])->toBe('Excelente!');
    });

    it('vitrine exibe avaliações reais quando existem com nota >= 4 e comentário', function () {
        $ag = makeRelAg();
        Avaliacao::create([
            'company_id' => $this->company->id,
            'agendamento_id' => $ag->id,
            'nota' => 5,
            'comentario' => 'Serviço impecável!',
        ]);

        $this->get(route('vitrine.show', $this->company->slug))
            ->assertOk()
            ->assertSee('Serviço impecável!');
    });

    it('vitrine usa depoimentos estáticos quando sem avaliações reais', function () {
        $this->get(route('vitrine.show', $this->company->slug))
            ->assertOk()
            ->assertSee('Miguel Santos');
    });
});
