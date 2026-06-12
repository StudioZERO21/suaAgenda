<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use App\Models\Venda;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia VPH', 'slug' => 'barbearia-vph',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makeVendaVph(string $companyId, float $total, Carbon $createdAt): Venda
{
    $venda = Venda::create([
        'company_id' => $companyId,
        'total' => $total,
        'desconto' => 0,
        'subtotal' => $total,
        'metodo_pagamento' => 'dinheiro',
    ]);
    $venda->created_at = $createdAt;
    $venda->save();

    return $venda;
}

describe('pdv_vendas_por_hora', function () {
    it('retorna estrutura correta sem vendas', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.por-hora'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'total_vendas', 'valor_total', 'horas', 'hora_pico']);
        expect($data['total_vendas'])->toBe(0);
        expect($data['horas'])->toHaveCount(24);
        expect($data['hora_pico'])->toBeNull();
    });

    it('cada entrada de hora tem campos corretos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.por-hora'))
            ->assertOk()
            ->json();

        $hora = $data['horas'][0];
        expect($hora)->toHaveKeys(['hora', 'hora_fmt', 'total_vendas', 'valor_total', 'ticket_medio']);
        expect($hora['hora'])->toBe(0);
        expect($hora['hora_fmt'])->toBe('00:00');
    });

    it('agrupa vendas pela hora correta', function () {
        $hora10 = now()->setHour(10)->setMinute(0)->setSecond(0)->subDays(1);
        $hora10b = now()->setHour(10)->setMinute(30)->setSecond(0)->subDays(1);
        $hora14 = now()->setHour(14)->setMinute(0)->setSecond(0)->subDays(1);

        makeVendaVph($this->company->id, 50.0, $hora10);
        makeVendaVph($this->company->id, 80.0, $hora10b);
        makeVendaVph($this->company->id, 30.0, $hora14);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.por-hora'))
            ->assertOk()
            ->json();

        $h10 = collect($data['horas'])->firstWhere('hora', 10);
        $h14 = collect($data['horas'])->firstWhere('hora', 14);

        expect($h10['total_vendas'])->toBe(2);
        expect((float) $h10['valor_total'])->toBe(130.0);
        expect((float) $h10['ticket_medio'])->toBe(65.0);
        expect($h14['total_vendas'])->toBe(1);
    });

    it('aponta hora_pico corretamente', function () {
        $hora10 = now()->setHour(10)->subDays(1);
        makeVendaVph($this->company->id, 50.0, $hora10);
        makeVendaVph($this->company->id, 60.0, $hora10);

        $hora15 = now()->setHour(15)->subDays(1);
        makeVendaVph($this->company->id, 40.0, $hora15);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.por-hora'))
            ->assertOk()
            ->json();

        expect($data['hora_pico']['hora'])->toBe(10);
        expect($data['hora_pico']['total_vendas'])->toBe(2);
    });

    it('ignora vendas de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra VPH', 'slug' => 'outra-vph', 'plano' => 'trial', 'ativo' => true]);
        makeVendaVph($outra->id, 100.0, now()->setHour(10));

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.por-hora'))
            ->assertOk()
            ->json();

        expect($data['total_vendas'])->toBe(0);
    });

    it('respeita parâmetro dias', function () {
        makeVendaVph($this->company->id, 50.0, now()->setHour(9)->subDays(5));
        makeVendaVph($this->company->id, 50.0, now()->setHour(9)->subDays(60));

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.por-hora', ['dias' => 30]))
            ->assertOk()
            ->json();

        expect($data['total_vendas'])->toBe(1);
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('pdv.vendas.por-hora'))
            ->assertUnauthorized();
    });
});
