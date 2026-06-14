<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Pix PDV',
        'slug' => 'barbearia-pix-pdv',
        'plano' => 'starter',
        'ativo' => true,
        'settings' => [
            'payments' => [
                'pix_key' => 'aae2196f-5f93-46e4-89e6-73bf4138427b',
                'pix_key_type' => 'random',
                'pix_city' => 'Sao Paulo',
            ],
        ],
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');
});

describe('pdv_pagamento_pix', function () {
    it('retorna qr code quando chave pix está configurada', function () {
        $response = $this->actingAs($this->admin)
            ->getJson(route('pdv.pagamento.pix', ['total' => 42.50]));

        $response->assertOk()
            ->assertJsonPath('configured', true)
            ->assertJsonStructure(['copy_paste', 'qr_code']);

        expect($response->json('copy_paste'))->toStartWith('000201');
    });

    it('informa quando chave pix não está configurada', function () {
        $this->company->update(['settings' => []]);

        $this->actingAs($this->admin)
            ->getJson(route('pdv.pagamento.pix', ['total' => 10]))
            ->assertOk()
            ->assertJsonPath('configured', false)
            ->assertJsonStructure(['message']);
    });

    it('valida total mínimo', function () {
        $this->actingAs($this->admin)
            ->getJson(route('pdv.pagamento.pix', ['total' => 0]))
            ->assertUnprocessable();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('pdv.pagamento.pix', ['total' => 10]))
            ->assertUnauthorized();
    });
});
