<?php

declare(strict_types=1);

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
        'name' => 'Barbearia ServDet', 'slug' => 'barbearia-servdet',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->servico = Servico::create([
        'company_id' => $this->company->id,
        'nome' => 'Corte Degradê',
        'descricao' => 'Corte moderno',
        'cor' => '#ff0000',
        'duracao_minutos' => 45,
        'preco' => 60.0,
        'ativo' => true,
    ]);
});

describe('servico_detalhe', function () {
    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.detalhe', $this->servico))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['id', 'nome', 'descricao', 'cor', 'duracao_minutos', 'preco', 'ativo', 'profissionais']);
    });

    it('retorna dados corretos do serviço', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.detalhe', $this->servico))
            ->json();

        expect($data['nome'])->toBe('Corte Degradê');
        expect($data['descricao'])->toBe('Corte moderno');
        expect($data['cor'])->toBe('#ff0000');
        expect($data['duracao_minutos'])->toBe(45);
        expect((float) $data['preco'])->toBe(60.0);
        expect($data['ativo'])->toBeTrue();
    });

    it('inclui profissionais ativos vinculados', function () {
        $prof1 = Profissional::create(['company_id' => $this->company->id, 'name' => 'André', 'ativo' => true]);
        $prof2 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Beatriz', 'ativo' => false]);
        $this->servico->profissionais()->sync([$prof1->id, $prof2->id]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.detalhe', $this->servico))
            ->json();

        expect(count($data['profissionais']))->toBe(1);
        expect($data['profissionais'][0]['name'])->toBe('André');
    });

    it('retorna profissionais vazio quando sem vínculos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.detalhe', $this->servico))
            ->json();

        expect($data['profissionais'])->toBeEmpty();
    });

    it('não acessa serviço de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-servdet', 'plano' => 'trial', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'duracao_minutos' => 30, 'preco' => 10.0, 'cor' => '#111', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->getJson(route('servicos.detalhe', $servOutra))
            ->assertForbidden();
    });

    it('analista pode ver detalhe', function () {
        $this->actingAs($this->analista)
            ->getJson(route('servicos.detalhe', $this->servico))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('servicos.detalhe', $this->servico))
            ->assertUnauthorized();
    });
});
