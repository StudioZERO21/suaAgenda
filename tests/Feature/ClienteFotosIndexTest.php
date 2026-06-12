<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\ClienteFoto;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia CliPhotos', 'slug' => 'barbearia-cliphotos',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001']);
});

function makeClienteFotoRecord(Cliente $cliente, string $tipo = 'outro', ?string $legenda = null): ClienteFoto
{
    return ClienteFoto::create([
        'cliente_id' => $cliente->id,
        'imagem_path' => "cliente_fotos/{$cliente->company_id}/test.jpg",
        'tipo' => $tipo,
        'legenda' => $legenda,
    ]);
}

describe('cliente_fotos_index', function () {
    it('retorna lista vazia quando sem fotos', function () {
        $this->actingAs($this->admin)
            ->getJson(route('clientes.fotos.index', $this->cliente))
            ->assertOk()
            ->assertJson([]);
    });

    it('retorna fotos do cliente com estrutura correta', function () {
        makeClienteFotoRecord($this->cliente, 'antes', 'Antes do corte');
        makeClienteFotoRecord($this->cliente, 'depois', 'Depois do corte');

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.fotos.index', $this->cliente))
            ->assertOk()
            ->json();

        expect(count($data))->toBe(2);
        expect($data[0])->toHaveKeys(['id', 'url', 'tipo', 'legenda', 'criado_em']);
    });

    it('retorna tipos corretamente', function () {
        makeClienteFotoRecord($this->cliente, 'antes');
        makeClienteFotoRecord($this->cliente, 'depois');

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.fotos.index', $this->cliente))
            ->json();

        $tipos = collect($data)->pluck('tipo')->all();
        expect($tipos)->toContain('antes');
        expect($tipos)->toContain('depois');
    });

    it('não acessa fotos de cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-cliphotos', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Z', 'phone' => '99999999999']);

        $this->actingAs($this->admin)
            ->getJson(route('clientes.fotos.index', $cliOutra))
            ->assertForbidden();
    });

    it('analista pode listar fotos', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.fotos.index', $this->cliente))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('clientes.fotos.index', $this->cliente))
            ->assertUnauthorized();
    });
});
