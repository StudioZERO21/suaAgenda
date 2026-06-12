<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\Venda;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia DS', 'slug' => 'barbearia-ds',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makeVendaDS(string $companyId, float $total, Carbon $createdAt): Venda
{
    $v = Venda::create([
        'company_id' => $companyId,
        'total' => $total,
        'status' => 'pago',
    ]);
    $v->created_at = $createdAt;
    $v->save();

    return $v;
}

describe('pdv_ticket_por_dia_semana', function () {
    it('retorna estrutura correta sem vendas', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.ticket-por-dia-semana'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'total_vendas', 'melhor_dia', 'serie']);
        expect($data['total_vendas'])->toBe(0);
        expect($data['melhor_dia'])->toBeNull();
        expect($data['serie'])->toHaveCount(7);
        expect($data['serie'][0]['dia_semana'])->toBe(0);
        expect($data['serie'][0]['dia_nome'])->toBe('Dom');
    });

    it('agrupa vendas por dia da semana corretamente', function () {
        $segunda = Carbon::now()->startOfWeek(Carbon::MONDAY);
        makeVendaDS($this->company->id, 100.0, $segunda);
        makeVendaDS($this->company->id, 80.0, $segunda);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.ticket-por-dia-semana'))
            ->assertOk()
            ->json();

        expect($data['total_vendas'])->toBe(2);

        $por_dia = collect($data['serie'])->keyBy('dia_semana');
        $dow = (int) $segunda->format('w');
        expect($por_dia[$dow]['total_vendas'])->toBe(2);
        expect((float) $por_dia[$dow]['valor_total'])->toBe(180.0);
        expect((float) $por_dia[$dow]['ticket_medio'])->toBe(90.0);
    });

    it('dias sem venda têm ticket_medio null', function () {
        $segunda = Carbon::now()->startOfWeek(Carbon::MONDAY);
        makeVendaDS($this->company->id, 50.0, $segunda);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.ticket-por-dia-semana'))
            ->assertOk()
            ->json();

        $por_dia = collect($data['serie'])->keyBy('dia_semana');
        // Domingo (0) deve ter ticket_medio null se não houve venda
        $dow = (int) $segunda->format('w');
        $outroDow = ($dow + 3) % 7;
        expect($por_dia[$outroDow]['ticket_medio'])->toBeNull();
    });

    it('filtra por periodo_dias', function () {
        $antiga = Carbon::now()->subDays(40);
        $recente = Carbon::now()->subDays(5);
        makeVendaDS($this->company->id, 100.0, $antiga);
        makeVendaDS($this->company->id, 50.0, $recente);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.ticket-por-dia-semana', ['periodo_dias' => 30]))
            ->assertOk()
            ->json();

        expect($data['total_vendas'])->toBe(1);
        expect($data['periodo_dias'])->toBe(30);
    });

    it('ignora vendas de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra DS', 'slug' => 'outra-ds', 'plano' => 'trial', 'ativo' => true]);
        makeVendaDS($outra->id, 200.0, Carbon::now());

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.ticket-por-dia-semana'))
            ->assertOk()
            ->json();

        expect($data['total_vendas'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('pdv.ticket-por-dia-semana'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('pdv.ticket-por-dia-semana'))
            ->assertUnauthorized();
    });
});
