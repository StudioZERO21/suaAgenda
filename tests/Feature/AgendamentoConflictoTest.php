<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Salão Conflito',
        'slug' => 'salao-conflito',
        'plano' => 'trial',
        'whatsapp' => '(11) 99999-0060',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->profA = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Profissional A',
        'ativo' => true,
    ]);

    $this->profB = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Profissional B',
        'ativo' => true,
    ]);

    $this->cliente1 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente 1']);
    $this->cliente2 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente 2']);
    $this->cliente3 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente 3']);
});

function criarAgConflito(array $extra = []): Agendamento
{
    return Agendamento::create(array_merge([
        'company_id' => test()->company->id,
        'profissional_id' => test()->profA->id,
        'cliente_id' => test()->cliente1->id,
        'data_hora' => now()->addDay()->setTime(10, 0),
        'duracao' => 60,
        'status' => 'pendente',
        'valor' => 50.00,
    ], $extra));
}

describe('agendamentos simultâneos (diferentes profissionais)', function () {
    it('permite dois profissionais no mesmo horário', function () {
        criarAgConflito(['profissional_id' => test()->profA->id]);

        // profB no MESMO horário — deve ser permitido
        $response = $this->actingAs($this->admin)
            ->post(route('agendamentos.store'), [
                'profissional_id' => $this->profB->id,
                'cliente_id' => $this->cliente2->id,
                'data_hora' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i'),
                'duracao' => 60,
                'valor' => 50,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        expect(Agendamento::count())->toBe(2);
    });

    it('permite cinco profissionais no mesmo horário', function () {
        $profissionais = collect();
        for ($i = 0; $i < 5; $i++) {
            $profissionais->push(Profissional::create([
                'company_id' => $this->company->id,
                'name' => "Prof {$i}",
                'ativo' => true,
            ]));
        }

        $clientes = collect();
        for ($i = 0; $i < 5; $i++) {
            $clientes->push(Cliente::create([
                'company_id' => $this->company->id,
                'name' => "Cliente X{$i}",
            ]));
        }

        foreach ($profissionais as $i => $prof) {
            Agendamento::create([
                'company_id' => $this->company->id,
                'profissional_id' => $prof->id,
                'cliente_id' => $clientes[$i]->id,
                'data_hora' => now()->addDay()->setTime(10, 0),
                'duracao' => 60,
                'status' => 'pendente',
                'valor' => 50,
            ]);
        }

        expect(Agendamento::count())->toBe(5);
    });
});

describe('conflito de horário — mesmo profissional', function () {
    it('bloqueia exact-time duplicado para o mesmo profissional', function () {
        criarAgConflito();

        $response = $this->actingAs($this->admin)
            ->post(route('agendamentos.store'), [
                'profissional_id' => $this->profA->id,
                'cliente_id' => $this->cliente2->id,
                'data_hora' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i'),
                'duracao' => 30,
                'valor' => 50,
            ]);

        $response->assertSessionHasErrors('data_hora');
        expect(Agendamento::count())->toBe(1);
    });

    it('bloqueia sobreposição parcial para o mesmo profissional', function () {
        // profA ocupa 10:00–11:00 (60 min)
        criarAgConflito(['duracao' => 60]);

        // Tenta marcar 10:30 para profA — overlap com o anterior
        $response = $this->actingAs($this->admin)
            ->post(route('agendamentos.store'), [
                'profissional_id' => $this->profA->id,
                'cliente_id' => $this->cliente2->id,
                'data_hora' => now()->addDay()->setTime(10, 30)->format('Y-m-d H:i'),
                'duracao' => 30,
                'valor' => 50,
            ]);

        $response->assertSessionHasErrors('data_hora');
        expect(Agendamento::count())->toBe(1);
    });

    it('permite horário imediatamente após o anterior para o mesmo profissional', function () {
        // profA ocupa 10:00–11:00 (60 min)
        criarAgConflito(['duracao' => 60]);

        // 11:00 exato — não há sobreposição
        $response = $this->actingAs($this->admin)
            ->post(route('agendamentos.store'), [
                'profissional_id' => $this->profA->id,
                'cliente_id' => $this->cliente2->id,
                'data_hora' => now()->addDay()->setTime(11, 0)->format('Y-m-d H:i'),
                'duracao' => 30,
                'valor' => 50,
            ]);

        $response->assertSessionHasNoErrors();
        expect(Agendamento::count())->toBe(2);
    });
});
