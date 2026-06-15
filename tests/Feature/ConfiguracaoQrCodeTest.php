<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia QR', 'slug' => 'barbearia-qr',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('configuracao_qrcode', function () {
    it('admin pode baixar QR code como SVG', function () {
        $this->actingAs($this->admin)
            ->get(route('configuracoes.empresa.qrcode'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    });

    it('SVG inline não tem Content-Disposition (compatível com img tag)', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('configuracoes.empresa.qrcode'))
            ->assertOk();

        // download é tratado pelo atributo HTML download= na view, não pelo header
        expect($response->headers->get('Content-Disposition'))->toBeNull();
    });

    it('gestor pode baixar QR code', function () {
        $this->actingAs($this->gestor)
            ->get(route('configuracoes.empresa.qrcode'))
            ->assertOk();
    });

    it('analista não pode baixar QR code (sem permissão de configurações)', function () {
        $this->actingAs($this->analista)
            ->get(route('configuracoes.empresa.qrcode'))
            ->assertForbidden();
    });

    it('unauthenticated é redirecionado', function () {
        $this->get(route('configuracoes.empresa.qrcode'))
            ->assertRedirect();
    });
});
