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
        'name' => 'QR Barbearia',
        'slug' => 'qr-barbearia',
        'plano' => 'trial',
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('qrcode_empresa', function () {
    it('admin pode baixar QR code', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('configuracoes.empresa.qrcode'));

        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('image/svg+xml');
    });

    it('analista não pode baixar QR code (sem permissão de configurações)', function () {
        $this->actingAs($this->analista)
            ->get(route('configuracoes.empresa.qrcode'))
            ->assertForbidden();
    });

    it('QR code contém SVG válido', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('configuracoes.empresa.qrcode'));

        $content = $response->getContent();
        expect($content)->toContain('<svg')
            ->and($content)->toContain('</svg>');
    });

    it('SVG inline não tem Content-Disposition (renderiza em img tag)', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('configuracoes.empresa.qrcode'));

        // sem attachment header — renderiza inline no <img>
        expect($response->headers->get('content-disposition'))->toBeNull();
    });

    it('não vaza QR de outra empresa: cada empresa recebe seu próprio SVG', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-qr', 'plano' => 'trial', 'ativo' => true]);
        $outro = User::factory()->create(['empresa_id' => $outra->id]);
        $outro->assignRole('admin_empresa');

        $svgOutro = $this->actingAs($outro)
            ->get(route('configuracoes.empresa.qrcode'))
            ->getContent();

        $svgMinha = $this->actingAs($this->admin)
            ->get(route('configuracoes.empresa.qrcode'))
            ->getContent();

        // ambos retornam SVG válido mas com conteúdo diferente (URL diferente encodada)
        expect($svgOutro)->toContain('<svg')
            ->and($svgMinha)->toContain('<svg')
            ->and($svgOutro)->not->toBe($svgMinha);
    });

    it('unauthenticated é redirecionado', function () {
        $this->get(route('configuracoes.empresa.qrcode'))->assertRedirect();
    });
});
