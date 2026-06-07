<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Teste',
        'slug' => 'empresa-teste',
        'plano' => 'trial',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Dr. Profissional',
        'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'Cliente Teste',
        'lgpd_consent' => true,
    ]);
});

describe('agendamentos', function () {
    it('lista agendamentos do usuário autenticado', function () {
        $this->actingAs($this->user)
            ->get(route('agendamentos.index'))
            ->assertStatus(200);
    });

    it('cria agendamento com sucesso', function () {
        $dataHora = now()->addDay()->format('Y-m-d H:i:s');

        $this->actingAs($this->user)
            ->post(route('agendamentos.store'), [
                'profissional_id' => $this->profissional->id,
                'cliente_id' => $this->cliente->id,
                'data_hora' => $dataHora,
                'duracao' => 60,
            ])
            ->assertRedirect();

        expect(Agendamento::where('company_id', $this->company->id)->count())->toBe(1);
    });

    it('cancela agendamento com soft delete', function () {
        $agendamento = Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'cliente_id' => $this->cliente->id,
            'data_hora' => now()->addDay(),
            'duracao' => 60,
            'status' => 'pendente',
        ]);

        $this->actingAs($this->user)
            ->delete(route('agendamentos.destroy', $agendamento))
            ->assertRedirect(route('agendamentos.index'));

        expect(Agendamento::find($agendamento->id))->toBeNull();
        expect(Agendamento::withTrashed()->find($agendamento->id))->not->toBeNull();
    });

    it('não permite data no passado', function () {
        $this->actingAs($this->user)
            ->post(route('agendamentos.store'), [
                'profissional_id' => $this->profissional->id,
                'cliente_id' => $this->cliente->id,
                'data_hora' => now()->subDay()->format('Y-m-d H:i:s'),
                'duracao' => 60,
            ])
            ->assertSessionHasErrors('data_hora');
    });

    it('impede acesso a agendamento de outra empresa', function () {
        $outraCompany = Company::create([
            'name' => 'Outra Empresa',
            'slug' => 'outra-empresa',
            'plano' => 'trial',
            'ativo' => true,
        ]);
        $outroProfissional = Profissional::create(['company_id' => $outraCompany->id, 'name' => 'Outro', 'ativo' => true]);
        $outroCliente = Cliente::create(['company_id' => $outraCompany->id, 'name' => 'Outro Cliente', 'lgpd_consent' => true]);

        $agendamentoOutro = Agendamento::create([
            'company_id' => $outraCompany->id,
            'profissional_id' => $outroProfissional->id,
            'cliente_id' => $outroCliente->id,
            'data_hora' => now()->addDay(),
            'duracao' => 60,
            'status' => 'pendente',
        ]);

        $this->actingAs($this->user)
            ->get(route('agendamentos.show', $agendamentoOutro))
            ->assertForbidden();
    });

    it('impede double booking do mesmo profissional', function () {
        $dataHora = now()->addDay()->format('Y-m-d H:i:s');

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'cliente_id' => $this->cliente->id,
            'data_hora' => $dataHora,
            'duracao' => 60,
            'status' => 'pendente',
        ]);

        $this->actingAs($this->user)
            ->post(route('agendamentos.store'), [
                'profissional_id' => $this->profissional->id,
                'cliente_id' => $this->cliente->id,
                'data_hora' => $dataHora,
                'duracao' => 60,
            ])
            ->assertSessionHasErrors('data_hora');
    });

    it('scope ativo exclui cancelados', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'cliente_id' => $this->cliente->id,
            'data_hora' => now()->addDay(),
            'duracao' => 60,
            'status' => 'cancelado',
        ]);

        expect(Agendamento::ativo()->count())->toBe(0);
    });
});
