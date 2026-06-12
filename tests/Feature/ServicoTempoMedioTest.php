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
        'name' => 'Barbearia ServTM', 'slug' => 'barbearia-servtm',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'lgpd_consent' => true]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id,
        'nome' => 'Corte Navalhado',
        'duracao_minutos' => 45,
        'preco' => 60,
        'ativo' => true,
    ]);
});

describe('servico_tempo_medio', function () {
    it('retorna estrutura correta sem agendamentos finalizados', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.tempo-medio', $this->servico))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys([
            'servico_id', 'servico_nome', 'duracao_configurada',
            'total_realizados', 'duracao_media', 'valor_medio', 'valor_total',
        ]);
        expect($data['total_realizados'])->toBe(0);
        expect($data['duracao_media'])->toBeNull();
        expect($data['valor_medio'])->toBeNull();
        expect((float) $data['valor_total'])->toBe(0.0);
        expect($data['duracao_configurada'])->toBe(45);
    });

    it('calcula médias a partir de agendamentos finalizados', function () {
        foreach ([40, 50, 45] as $duracao) {
            Agendamento::create([
                'company_id' => $this->company->id,
                'profissional_id' => $this->prof->id,
                'cliente_id' => $this->cliente->id,
                'servico_id' => $this->servico->id,
                'data_hora' => now()->subDays(rand(1, 30)),
                'duracao' => $duracao,
                'valor' => 60,
                'status' => Agendamento::STATUS_FINALIZADO,
                'cancel_token' => Agendamento::generateCancelToken(),
            ]);
        }

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.tempo-medio', $this->servico))
            ->assertOk()
            ->json();

        expect($data['total_realizados'])->toBe(3);
        expect((float) $data['duracao_media'])->toBe(45.0);
        expect((float) $data['valor_total'])->toBe(180.0);
    });

    it('ignora agendamentos não finalizados', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay(),
            'duracao' => 45,
            'valor' => 60,
            'status' => Agendamento::STATUS_CONFIRMADO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.tempo-medio', $this->servico))
            ->assertOk()
            ->json();

        expect($data['total_realizados'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('servicos.tempo-medio', $this->servico))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('servicos.tempo-medio', $this->servico))
            ->assertUnauthorized();
    });
});
