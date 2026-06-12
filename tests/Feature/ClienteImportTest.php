<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Import', 'slug' => 'barbearia-import',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');
});

function makeCsvFile(string $content): UploadedFile
{
    $tmpPath = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($tmpPath, $content);

    return new UploadedFile($tmpPath, 'clientes.csv', 'text/csv', null, true);
}

describe('cliente_importar_csv', function () {
    it('importa clientes válidos do CSV', function () {
        $csv = "nome;telefone;email\nAna Silva;11999990001;ana@test.com\nBob Costa;11999990002;bob@test.com";
        $file = makeCsvFile($csv);

        $response = $this->actingAs($this->user)
            ->postJson(route('clientes.importar'), ['arquivo' => $file]);

        $response->assertOk()->assertJson(['importados' => 2, 'erros' => 0]);
        expect(Cliente::where('company_id', $this->company->id)->count())->toBe(2);
    });

    it('conta como erro linha sem nome', function () {
        $csv = "nome;telefone\n;11999990001\nCarla Melo;11999990002";
        $file = makeCsvFile($csv);

        $response = $this->actingAs($this->user)
            ->postJson(route('clientes.importar'), ['arquivo' => $file]);

        $response->assertOk()->assertJson(['importados' => 1, 'erros' => 1]);
    });

    it('não duplica cliente com mesmo telefone', function () {
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Existente', 'phone' => '11999990001']);

        $csv = "nome;telefone\nExistente;11999990001\nNovo;11999990002";
        $file = makeCsvFile($csv);

        $this->actingAs($this->user)
            ->postJson(route('clientes.importar'), ['arquivo' => $file]);

        expect(Cliente::where('company_id', $this->company->id)->count())->toBe(2);
    });

    it('associa clientes importados à empresa do usuário', function () {
        $csv = "nome;telefone\nJoão Importado;11999990099";
        $file = makeCsvFile($csv);

        $this->actingAs($this->user)
            ->postJson(route('clientes.importar'), ['arquivo' => $file]);

        $cliente = Cliente::where('phone', '11999990099')->first();
        expect($cliente?->company_id)->toBe($this->company->id);
    });

    it('rejeita arquivo sem campo arquivo', function () {
        $this->actingAs($this->user)
            ->postJson(route('clientes.importar'), [])
            ->assertUnprocessable();
    });

    it('unauthenticated é rejeitado', function () {
        $csv = "nome;telefone\nAna;11999990001";
        $file = makeCsvFile($csv);

        $this->postJson(route('clientes.importar'), ['arquivo' => $file])
            ->assertUnauthorized();
    });
});
