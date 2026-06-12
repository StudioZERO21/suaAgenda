<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['activitylog.enabled' => true]);

    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'company_id' => null]);
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web', 'company_id' => null]);

    $this->company = Company::create(['name' => 'Empresa A', 'slug' => 'empresa-aud', 'plano' => 'trial', 'ativo' => true]);

    $this->super = User::create([
        'name' => 'Super', 'email' => 'super@aud.test',
        'password' => bcrypt('secret123'), 'ativo' => true,
    ]);
    $this->super->assignRole('super_admin');

    $this->admin = User::create([
        'name' => 'Admin', 'email' => 'admin@aud.test',
        'password' => bcrypt('secret123'), 'empresa_id' => $this->company->id, 'ativo' => true,
    ]);
    $this->admin->assignRole('admin_empresa');
});

describe('admin_auditoria', function () {
    it('registra criação de cliente com company_id e causer', function () {
        $this->actingAs($this->admin);

        $cliente = Cliente::create([
            'company_id' => $this->company->id, 'name' => 'Maria', 'phone' => '11999990000',
        ]);

        $atividade = Activity::where('subject_type', Cliente::class)
            ->where('subject_id', $cliente->id)
            ->where('event', 'created')
            ->first();

        expect($atividade)->not->toBeNull()
            ->and($atividade->company_id)->toBe($this->company->id)
            ->and($atividade->causer_id)->toBe($this->admin->id);
    });

    it('registra alterações com valores antes/depois', function () {
        $this->actingAs($this->admin);

        $cliente = Cliente::create([
            'company_id' => $this->company->id, 'name' => 'Maria', 'phone' => '11999990000',
        ]);
        $cliente->update(['name' => 'Maria Silva']);

        $atividade = Activity::where('subject_type', Cliente::class)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $mudancas = $atividade->attribute_changes->toArray();
        expect($mudancas['attributes']['name'])->toBe('Maria Silva')
            ->and($mudancas['old']['name'])->toBe('Maria');
    });

    it('registra login na trilha de auth', function () {
        $this->post('/login', ['email' => 'admin@aud.test', 'password' => 'secret123'])
            ->assertRedirect();

        expect(Activity::where('log_name', 'auth')->where('event', 'login')->count())->toBe(1);
    });

    it('portal lista atividades com filtro por empresa', function () {
        $outra = Company::create(['name' => 'Empresa B', 'slug' => 'empresa-aud-b', 'plano' => 'trial', 'ativo' => true]);

        $this->actingAs($this->admin);
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Da Empresa A', 'phone' => '111']);

        auth()->logout();
        Cliente::create(['company_id' => $outra->id, 'name' => 'Da Empresa B', 'phone' => '222']);

        $resposta = $this->actingAs($this->super)
            ->getJson(route('admin.auditoria.json', ['empresa_id' => $this->company->id]))
            ->assertOk()
            ->json();

        $descricoes = collect($resposta['items'])->pluck('company_id')->unique()->filter()->values();
        expect($descricoes->all())->toBe([$this->company->id]);
    });

    it('apenas super_admin acessa o portal de auditoria', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.auditoria.index'))
            ->assertForbidden();

        $this->actingAs($this->super)
            ->get(route('admin.auditoria.index'))
            ->assertOk()
            ->assertViewIs('admin.auditoria');
    });

    it('painel de saúde responde para super_admin', function () {
        $this->actingAs($this->super)
            ->get(route('admin.saude.index'))
            ->assertOk()
            ->assertViewIs('admin.saude')
            ->assertSee('Saúde do Sistema');
    });
});
