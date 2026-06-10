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

    $this->company = Company::create([
        'name' => 'Barbearia Av', 'slug' => 'barbearia-av',
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

function makeAvAg($self, int $nota, string $comentario = '', int $diasAtras = 5): Avaliacao
{
    $ag = Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->subDays($diasAtras),
        'duracao' => 30,
        'status' => 'finalizado',
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);

    return Avaliacao::create([
        'company_id' => $self->company->id,
        'agendamento_id' => $ag->id,
        'nota' => $nota,
        'comentario' => $comentario ?: null,
    ]);
}

describe('relatorio_avaliacoes_export', function () {
    it('exporta CSV com header correto', function () {
        $response = $this->actingAs($this->user)
            ->get(route('relatorios.avaliacoes.exportar'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        expect($response->streamedContent())->toContain('Nota');
        expect($response->streamedContent())->toContain('Estrelas');
        expect($response->streamedContent())->toContain('Comentário');
    });

    it('inclui dados da avaliação no CSV', function () {
        makeAvAg($this, 5, 'Excelente!');

        $content = $this->actingAs($this->user)
            ->get(route('relatorios.avaliacoes.exportar'))
            ->streamedContent();

        expect($content)->toContain('João');
        expect($content)->toContain('Corte');
        expect($content)->toContain('Carlos');
        expect($content)->toContain('5');
        expect($content)->toContain('★★★★★');
        expect($content)->toContain('Excelente!');
    });

    it('inclui avaliação sem comentário', function () {
        makeAvAg($this, 3);

        $content = $this->actingAs($this->user)
            ->get(route('relatorios.avaliacoes.exportar'))
            ->streamedContent();

        expect($content)->toContain('★★★☆☆');
    });

    it('não expõe avaliações de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-av', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);
        $svcOutra = Servico::create([
            'company_id' => $outra->id, 'nome' => 'Svc Outra',
            'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#000', 'ativo' => true,
        ]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Intruso', 'phone' => '99999999999']);

        $ag = Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $svcOutra->id,
            'data_hora' => now()->subDays(3), 'duracao' => 30,
            'status' => 'finalizado', 'cancel_token' => Agendamento::generateCancelToken(),
        ]);
        Avaliacao::create([
            'company_id' => $outra->id, 'agendamento_id' => $ag->id,
            'nota' => 5, 'comentario' => 'Intruso Comentario',
        ]);

        $content = $this->actingAs($this->user)
            ->get(route('relatorios.avaliacoes.exportar'))
            ->streamedContent();

        expect($content)->not->toContain('Intruso Comentario');
    });

    it('retorna CSV vazio quando não há avaliações', function () {
        $content = $this->actingAs($this->user)
            ->get(route('relatorios.avaliacoes.exportar'))
            ->streamedContent();

        expect($content)->toContain('Nota');
        $lines = array_filter(explode("\n", trim($content)));
        expect(count($lines))->toBe(1);
    });

    it('unauthenticated é redirecionado', function () {
        $this->get(route('relatorios.avaliacoes.exportar'))
            ->assertRedirect(route('login'));
    });
});
