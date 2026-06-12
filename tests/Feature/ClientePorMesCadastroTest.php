<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia PMC', 'slug' => 'barbearia-pmc',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('clientes_por_mes_cadastro', function () {
    it('retorna 12 meses com zeros sem clientes', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.por-mes-cadastro'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['ano', 'total_ano', 'meses']);
        expect($data['meses'])->toHaveCount(12);
        expect($data['total_ano'])->toBe(0);
        expect($data['meses'][0])->toHaveKeys(['mes', 'mes_nome', 'total', 'ativos']);
    });

    it('conta clientes no mês correto', function () {
        $ano = now()->year;
        $c1 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Jan 1', 'lgpd_consent' => true]);
        $c1->created_at = Carbon::createFromDate($ano, 1, 15);
        $c1->save();

        $c2 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Jan 2', 'lgpd_consent' => true]);
        $c2->created_at = Carbon::createFromDate($ano, 1, 20);
        $c2->save();

        $c3 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Mar 1', 'lgpd_consent' => true]);
        $c3->created_at = Carbon::createFromDate($ano, 3, 5);
        $c3->save();

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.por-mes-cadastro', ['ano' => $ano]))
            ->assertOk()
            ->json();

        expect($data['total_ano'])->toBe(3);

        $janeiro = collect($data['meses'])->firstWhere('mes', 1);
        expect($janeiro['total'])->toBe(2);

        $marco = collect($data['meses'])->firstWhere('mes', 3);
        expect($marco['total'])->toBe(1);
    });

    it('aceita parâmetro ano', function () {
        $anoAnterior = now()->year - 1;
        $c = Cliente::create(['company_id' => $this->company->id, 'name' => 'Antigo', 'lgpd_consent' => true]);
        $c->created_at = Carbon::createFromDate($anoAnterior, 6, 1);
        $c->save();

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.por-mes-cadastro', ['ano' => $anoAnterior]))
            ->assertOk()
            ->json();

        expect($data['ano'])->toBe($anoAnterior);
        expect($data['total_ano'])->toBe(1);
    });

    it('ignora clientes de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pmc', 'plano' => 'trial', 'ativo' => true]);
        Cliente::create(['company_id' => $outra->id, 'name' => 'Fora', 'lgpd_consent' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.por-mes-cadastro'))
            ->assertOk()
            ->json();

        expect($data['total_ano'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.por-mes-cadastro'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('clientes.por-mes-cadastro'))
            ->assertUnauthorized();
    });
});
