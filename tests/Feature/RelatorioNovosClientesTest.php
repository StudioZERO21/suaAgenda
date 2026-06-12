<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia NC', 'slug' => 'barbearia-nc',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makeNcCliente(string $companyId, string $name): Cliente
{
    return Cliente::create(['company_id' => $companyId, 'name' => $name, 'ativo' => true]);
}

describe('relatorio_novos_clientes', function () {
    it('retorna estrutura correta quando sem clientes no período', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.novos-clientes'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo', 'total', 'por_semana']);
        expect($data['total'])->toBe(0);
        expect($data['por_semana'])->toBeArray();
    });

    it('conta novos clientes no período corretamente', function () {
        makeNcCliente($this->company->id, 'A');
        makeNcCliente($this->company->id, 'B');
        makeNcCliente($this->company->id, 'C');

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.novos-clientes'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(3);
        expect(count($data['por_semana']))->toBeGreaterThanOrEqual(1);
    });

    it('estrutura de cada semana tem campos obrigatórios', function () {
        makeNcCliente($this->company->id, 'X');
        makeNcCliente($this->company->id, 'Y');

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.novos-clientes'))
            ->assertOk()
            ->json();

        expect($data['por_semana'][0])->toHaveKeys(['semana', 'label', 'novos', 'acumulado']);
    });

    it('acumulado cresce monotonicamente e bate com total', function () {
        makeNcCliente($this->company->id, 'P');
        makeNcCliente($this->company->id, 'Q');
        makeNcCliente($this->company->id, 'R');
        makeNcCliente($this->company->id, 'S');

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.novos-clientes'))
            ->assertOk()
            ->json();

        $semanas = $data['por_semana'];
        for ($i = 1; $i < count($semanas); $i++) {
            expect($semanas[$i]['acumulado'])->toBeGreaterThanOrEqual($semanas[$i - 1]['acumulado']);
        }
        expect(end($semanas)['acumulado'])->toBe($data['total']);
    });

    it('não inclui clientes de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-nc', 'plano' => 'trial', 'ativo' => true]);
        makeNcCliente($outra->id, 'Z1');
        makeNcCliente($outra->id, 'Z2');
        makeNcCliente($outra->id, 'Z3');

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.novos-clientes'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('relatorios.novos-clientes'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('relatorios.novos-clientes'))
            ->assertUnauthorized();
    });
});
