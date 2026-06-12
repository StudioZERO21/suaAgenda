<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ClienteFoto;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Support\ActivityLogStatus;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'company_id' => null]);
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web', 'company_id' => null]);

    $this->company = Company::create(['name' => 'Empresa LGPD', 'slug' => 'empresa-lgpd', 'plano' => 'trial', 'ativo' => true]);

    $this->admin = User::create([
        'name' => 'Admin', 'email' => 'admin@lgpd.test',
        'password' => bcrypt('secret123'), 'empresa_id' => $this->company->id, 'ativo' => true,
    ]);
    $this->admin->assignRole('admin_empresa');

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'Maria Titular', 'phone' => '11999990000',
        'email' => 'maria@titular.test', 'lgpd_consent' => true,
    ]);
});

describe('lgpd', function () {
    it('exporta os dados do titular em JSON', function () {
        $servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'cor' => '#111', 'ativo' => true]);
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'João', 'ativo' => true]);
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $prof->id, 'servico_id' => $servico->id,
            'data_hora' => now()->subDay(), 'duracao' => 30, 'valor' => 50,
            'status' => Agendamento::STATUS_FINALIZADO,
        ]);

        $resposta = $this->actingAs($this->admin)
            ->get(route('clientes.exportar-dados', $this->cliente))
            ->assertOk()
            ->assertHeader('Content-Disposition');

        $dados = $resposta->json();
        expect($dados['dados_pessoais']['nome'])->toBe('Maria Titular')
            ->and($dados['agendamentos'])->toHaveCount(1)
            ->and($dados['agendamentos'][0]['servico'])->toBe('Corte');
    });

    it('anonimiza o titular de forma irreversível', function () {
        config(['activitylog.enabled' => true]);
        app(ActivityLogStatus::class)->enable();
        Storage::fake('public');

        ClienteFoto::create([
            'cliente_id' => $this->cliente->id,
            'imagem_path' => 'clientes/foto.jpg', 'tipo' => 'antes',
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('clientes.anonimizar', $this->cliente))
            ->assertOk()
            ->assertJson(['success' => true]);

        $cliente = Cliente::withTrashed()->find($this->cliente->id);

        expect($cliente->name)->toBe('Cliente anonimizado')
            ->and($cliente->phone)->toBeNull()
            ->and($cliente->email)->toBeNull()
            ->and($cliente->anonymized_at)->not->toBeNull()
            ->and($cliente->trashed())->toBeTrue()
            ->and(ClienteFoto::count())->toBe(0);

        expect(Activity::inLog('lgpd')->forEvent('anonimizado')->count())->toBe(1);
    });

    it('não anonimiza duas vezes', function () {
        $this->actingAs($this->admin)
            ->postJson(route('clientes.anonimizar', $this->cliente))
            ->assertOk();

        // cliente foi soft-deletado: nova tentativa → 404
        $this->actingAs($this->admin)
            ->postJson(route('clientes.anonimizar', $this->cliente))
            ->assertNotFound();
    });

    it('registra consentimento com timestamp e IP', function () {
        $this->cliente->update(['lgpd_consent' => false, 'lgpd_consent_at' => null]);

        $this->actingAs($this->admin)
            ->patchJson(route('clientes.lgpd', $this->cliente), ['consent' => true])
            ->assertOk();

        $cliente = $this->cliente->fresh();
        expect($cliente->lgpd_consent)->toBeTrue()
            ->and($cliente->lgpd_consent_at)->not->toBeNull()
            ->and($cliente->lgpd_consent_ip)->not->toBeNull();
    });

    it('não exporta dados de cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-lgpd', 'plano' => 'trial', 'ativo' => true]);
        $clienteOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'De Outra', 'phone' => '222']);

        $this->actingAs($this->admin)
            ->get(route('clientes.exportar-dados', $clienteOutra))
            ->assertForbidden();
    });

    it('comando de retenção não anonimiza sem prazo configurado', function () {
        $this->artisan('lgpd:retencao')->assertSuccessful();

        expect($this->cliente->fresh()->anonymized_at)->toBeNull();
    });

    it('comando de retenção anonimiza com --meses para inativos antigos', function () {
        $antigo = Cliente::create([
            'company_id' => $this->company->id, 'name' => 'Antigo', 'phone' => '333',
        ]);
        Cliente::where('id', $antigo->id)->update(['created_at' => now()->subYears(3)]);

        $this->artisan('lgpd:retencao --meses=24')->assertSuccessful();

        expect(Cliente::withTrashed()->find($antigo->id)->anonymized_at)->not->toBeNull()
            ->and($this->cliente->fresh()->anonymized_at)->toBeNull();
    });

    it('portal LGPD do super_admin mostra consent por empresa', function () {
        $super = User::create([
            'name' => 'Super', 'email' => 'super@lgpd.test',
            'password' => bcrypt('secret123'), 'ativo' => true,
        ]);
        $super->assignRole('super_admin');

        $this->actingAs($super)
            ->get(route('admin.lgpd.index'))
            ->assertOk()
            ->assertViewIs('admin.lgpd')
            ->assertSee('Empresa LGPD');

        $this->actingAs($this->admin)
            ->get(route('admin.lgpd.index'))
            ->assertForbidden();
    });
});
