<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Cliente;
use App\Models\ClienteFoto;
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
        'name' => 'Barbearia CliDet', 'slug' => 'barbearia-clidet',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'Fernanda Silva',
        'phone' => '11999990077',
        'email' => 'fernanda@test.com',
        'ativo' => true,
    ]);
});

describe('cliente_detalhe', function () {
    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.detalhe', $this->cliente))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['id', 'name', 'email', 'phone', 'data_nasc', 'observacao', 'ativo', 'lgpd_consent', 'stats', 'fotos']);
        expect($data['stats'])->toHaveKeys(['total_agendamentos', 'finalizados', 'receita_total', 'nota_media', 'ultimo_agendamento']);
    });

    it('retorna dados corretos do cliente', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.detalhe', $this->cliente))
            ->json();

        expect($data['name'])->toBe('Fernanda Silva');
        expect($data['email'])->toBe('fernanda@test.com');
        expect($data['ativo'])->toBeTrue();
        expect($data['stats']['total_agendamentos'])->toBe(0);
    });

    it('inclui stats calculadas', function () {
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'P', 'ativo' => true]);
        $serv = Servico::create(['company_id' => $this->company->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);

        $ag = Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $serv->id,
            'data_hora' => now()->subDay(),
            'duracao' => 30,
            'valor' => 80.0,
            'status' => 'finalizado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        Avaliacao::create([
            'company_id' => $this->company->id,
            'agendamento_id' => $ag->id,
            'nota' => 5,
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.detalhe', $this->cliente))
            ->json();

        expect($data['stats']['total_agendamentos'])->toBe(1);
        expect($data['stats']['finalizados'])->toBe(1);
        expect((float) $data['stats']['receita_total'])->toBe(80.0);
        expect((float) $data['stats']['nota_media'])->toBe(5.0);
    });

    it('inclui fotos quando existirem', function () {
        ClienteFoto::create([
            'cliente_id' => $this->cliente->id,
            'imagem_path' => 'cliente_fotos/test/foto.jpg',
            'tipo' => 'antes',
            'legenda' => 'Antes do corte',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.detalhe', $this->cliente))
            ->json();

        expect(count($data['fotos']))->toBe(1);
        expect($data['fotos'][0])->toHaveKeys(['id', 'url', 'tipo', 'legenda']);
        expect($data['fotos'][0]['tipo'])->toBe('antes');
    });

    it('não acessa cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-clidet', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'X', 'phone' => '11999990000']);

        $this->actingAs($this->admin)
            ->getJson(route('clientes.detalhe', $cliOutra))
            ->assertForbidden();
    });

    it('analista pode ver detalhe', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.detalhe', $this->cliente))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('clientes.detalhe', $this->cliente))
            ->assertUnauthorized();
    });
});
