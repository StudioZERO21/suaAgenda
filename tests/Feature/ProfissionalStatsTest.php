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
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Stats', 'slug' => 'barbearia-stats',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001']);
});

function makeStatsAg($self, string $status = 'finalizado', float $valor = 100.0, int $diasAtras = 5): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->subDays($diasAtras),
        'duracao' => 30,
        'status' => $status,
        'valor' => $valor,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('profissional_stats', function () {
    it('admin pode acessar stats do profissional', function () {
        $this->actingAs($this->user)
            ->getJson(route('profissionais.stats', $this->prof))
            ->assertOk()
            ->assertJsonStructure(['profissional', 'total', 'finalizados', 'receita', 'nota_media', 'taxa_conclusao']);
    });

    it('retorna contagem e receita corretas', function () {
        makeStatsAg($this, 'finalizado', 150.0);
        makeStatsAg($this, 'finalizado', 100.0);
        makeStatsAg($this, 'pendente', 80.0);

        $this->actingAs($this->user)
            ->getJson(route('profissionais.stats', $this->prof))
            ->assertOk()
            ->assertJson([
                'total' => 3,
                'finalizados' => 2,
                'receita' => 250.0,
            ]);
    });

    it('taxa de conclusão calculada corretamente', function () {
        makeStatsAg($this, 'finalizado');
        makeStatsAg($this, 'finalizado');
        makeStatsAg($this, 'cancelado');
        makeStatsAg($this, 'pendente');

        $response = $this->actingAs($this->user)
            ->getJson(route('profissionais.stats', $this->prof));

        expect((float) $response->json('taxa_conclusao'))->toBe(50.0);
    });

    it('nota_media calculada corretamente', function () {
        $ag = makeStatsAg($this, 'finalizado');
        $ag->update(['status' => 'finalizado']);
        Avaliacao::create([
            'company_id' => $this->company->id,
            'agendamento_id' => $ag->id,
            'nota' => 4,
            'comentario' => 'Bom!',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('profissionais.stats', $this->prof));

        expect((float) $response->json('nota_media'))->toBe(4.0);
    });

    it('não acessa stats de profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-stats', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->user)
            ->getJson(route('profissionais.stats', $profOutra))
            ->assertForbidden();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('profissionais.stats', $this->prof))
            ->assertUnauthorized();
    });
});
