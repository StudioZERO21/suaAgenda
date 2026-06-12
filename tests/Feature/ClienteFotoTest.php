<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\ClienteFoto;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor',        'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista',      'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Foto Teste',
        'slug' => 'empresa-foto-teste',
        'plano' => 'trial',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'Cliente Foto',
        'lgpd_consent' => true,
    ]);
});

describe('ClienteFoto', function () {
    test('admin can upload client photo', function () {
        $file = UploadedFile::fake()->image('foto.jpg', 200, 200);

        $response = $this->actingAs($this->admin)
            ->postJson(route('clientes.fotos.store', $this->cliente), [
                'imagem' => $file,
                'tipo' => 'antes',
                'legenda' => 'Antes do corte',
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['id', 'url', 'tipo', 'legenda']);

        expect(ClienteFoto::where('cliente_id', $this->cliente->id)->count())->toBe(1);
        Storage::disk('public')->assertExists(
            ClienteFoto::where('cliente_id', $this->cliente->id)->first()->imagem_path
        );
    });

    test('cannot upload to another company client', function () {
        $outraCompany = Company::create([
            'name' => 'Outra Empresa',
            'slug' => 'outra-empresa-foto',
            'plano' => 'trial',
            'ativo' => true,
        ]);

        $clienteOutro = Cliente::create([
            'company_id' => $outraCompany->id,
            'name' => 'Cliente Alheio',
            'lgpd_consent' => false,
        ]);

        $file = UploadedFile::fake()->image('foto2.jpg');

        $this->actingAs($this->admin)
            ->postJson(route('clientes.fotos.store', $clienteOutro), [
                'imagem' => $file,
            ])
            ->assertForbidden();

        expect(ClienteFoto::where('cliente_id', $clienteOutro->id)->count())->toBe(0);
    });

    test('can delete own company client photo', function () {
        $file = UploadedFile::fake()->image('del.jpg');
        $path = $file->store("cliente_fotos/{$this->company->id}", 'public');

        $foto = ClienteFoto::create([
            'cliente_id' => $this->cliente->id,
            'imagem_path' => $path,
            'tipo' => 'depois',
        ]);

        Storage::disk('public')->assertExists($path);

        $this->actingAs($this->admin)
            ->deleteJson(route('clientes.fotos.destroy', $foto))
            ->assertNoContent();

        expect(ClienteFoto::find($foto->id))->toBeNull();
        Storage::disk('public')->assertMissing($path);
    });
});
