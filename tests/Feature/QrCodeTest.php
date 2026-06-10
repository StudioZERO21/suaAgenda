<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

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

    it('analista pode baixar QR code', function () {
        $response = $this->actingAs($this->analista)
            ->get(route('configuracoes.empresa.qrcode'));

        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('image/svg+xml');
    });

    it('QR code contém SVG válido', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('configuracoes.empresa.qrcode'));

        $content = $response->getContent();
        expect($content)->toContain('<svg')
            ->and($content)->toContain('</svg>');
    });

    it('Content-Disposition inclui slug da empresa', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('configuracoes.empresa.qrcode'));

        $disposition = $response->headers->get('content-disposition');
        expect($disposition)->toContain('qrcode-qr-barbearia.svg');
    });

    it('não vaza QR de outra empresa: Content-Disposition usa slug próprio', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-qr', 'plano' => 'trial', 'ativo' => true]);
        $outro = User::factory()->create(['empresa_id' => $outra->id]);
        $outro->assignRole('admin_empresa');

        $outroDisposition = $this->actingAs($outro)
            ->get(route('configuracoes.empresa.qrcode'))
            ->headers->get('content-disposition');

        $minhaDisposition = $this->actingAs($this->admin)
            ->get(route('configuracoes.empresa.qrcode'))
            ->headers->get('content-disposition');

        expect($outroDisposition)->toContain('outra-qr')
            ->and($minhaDisposition)->toContain('qr-barbearia')
            ->and($minhaDisposition)->not->toContain('outra-qr');
    });

    it('unauthenticated é redirecionado', function () {
        $this->get(route('configuracoes.empresa.qrcode'))->assertRedirect();
    });
});
