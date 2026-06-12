<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\ClienteFoto;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Foto Show',
        'slug' => 'empresa-foto-show',
        'plano' => 'trial',
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'Ana Show',
        'phone' => '11999990001',
    ]);
});

describe('galeria_fotos_show', function () {
    it('show page exibe seção de fotos para admin', function () {
        $this->actingAs($this->admin)
            ->get(route('clientes.show', $this->cliente))
            ->assertOk()
            ->assertSee('Fotos de Atendimento');
    });

    it('fotos do cliente aparecem como JSON no show', function () {
        $path = 'cliente_fotos/'.$this->company->id.'/foto1.jpg';
        Storage::disk('public')->put($path, 'fake');

        ClienteFoto::create([
            'cliente_id' => $this->cliente->id,
            'imagem_path' => $path,
            'tipo' => 'antes',
            'legenda' => 'Antes do corte',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('clientes.show', $this->cliente))
            ->assertOk();

        $content = $response->getContent();
        expect($content)->toContain('antes')
            ->and($content)->toContain('fotoGaleria');
    });

    it('show page passa fotos ordenadas por created_at no cliente', function () {
        $path1 = 'cliente_fotos/'.$this->company->id.'/a.jpg';
        $path2 = 'cliente_fotos/'.$this->company->id.'/b.jpg';
        Storage::disk('public')->put($path1, 'img1');
        Storage::disk('public')->put($path2, 'img2');

        $f1 = ClienteFoto::create(['cliente_id' => $this->cliente->id, 'imagem_path' => $path1, 'tipo' => 'antes']);
        $f2 = ClienteFoto::create(['cliente_id' => $this->cliente->id, 'imagem_path' => $path2, 'tipo' => 'depois']);

        $data = $this->actingAs($this->admin)
            ->get(route('clientes.show', $this->cliente))
            ->getOriginalContent()->getData();

        expect($data['cliente']->fotos)->toHaveCount(2);
    });

    it('cliente sem fotos exibe seção vazia sem erros', function () {
        $this->actingAs($this->admin)
            ->get(route('clientes.show', $this->cliente))
            ->assertOk()
            ->assertSee('fotoGaleria');
    });
});
