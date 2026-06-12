<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\Company;
use App\Models\CompanyRegra;
use App\Models\RegraCatalogo;
use App\Models\Role;
use App\Models\User;
use App\Services\RegraService;
use Database\Seeders\RegraCatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'company_id' => null]);
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web', 'company_id' => null]);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web', 'company_id' => null]);

    $this->seed(RegraCatalogoSeeder::class);

    $this->company = Company::create(['name' => 'Empresa Regras', 'slug' => 'empresa-regras', 'plano' => 'trial', 'ativo' => true]);

    $this->admin = User::create([
        'name' => 'Admin', 'email' => 'admin@regras.test',
        'password' => bcrypt('secret123'), 'empresa_id' => $this->company->id, 'ativo' => true,
    ]);
    $this->admin->assignRole('admin_empresa');

    $this->super = User::create([
        'name' => 'Super', 'email' => 'super@regras.test',
        'password' => bcrypt('secret123'), 'ativo' => true,
    ]);
    $this->super->assignRole('super_admin');
});

describe('regras_negocio', function () {
    it('seeder popula o catálogo com as 5 regras base', function () {
        expect(RegraCatalogo::count())->toBe(5)
            ->and(RegraCatalogo::where('codigo', 'cancelamento_antecedencia')->exists())->toBeTrue();
    });

    it('empresa vê a lista de regras disponíveis', function () {
        $this->actingAs($this->admin)
            ->get(route('regras.index'))
            ->assertOk()
            ->assertViewIs('configuracoes.regras');
    });

    it('analista não acessa as regras (sem cfg_rules)', function () {
        $analista = User::create([
            'name' => 'Analista', 'email' => 'analista@regras.test',
            'password' => bcrypt('secret123'), 'empresa_id' => $this->company->id, 'ativo' => true,
        ]);
        $analista->assignRole('analista');

        $this->actingAs($analista)
            ->get(route('regras.index'))
            ->assertForbidden();
    });

    it('empresa ativa e configura uma regra', function () {
        $this->actingAs($this->admin)
            ->putJson(route('regras.update', 'cancelamento_antecedencia'), [
                'ativo' => true,
                'params' => ['horas_min' => 48],
            ])
            ->assertOk()
            ->assertJsonPath('ativo', true)
            ->assertJsonPath('params.horas_min', 48);

        $servico = app(RegraService::class);
        expect($servico->enabled('cancelamento_antecedencia', $this->company->id))->toBeTrue()
            ->and($servico->param('cancelamento_antecedencia', 'horas_min', null, $this->company->id))->toBe(48);
    });

    it('valida params contra o schema do catálogo', function () {
        $this->actingAs($this->admin)
            ->putJson(route('regras.update', 'cancelamento_antecedencia'), [
                'ativo' => true,
                'params' => ['horas_min' => 9999],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('params.horas_min');
    });

    it('params usam defaults do catálogo quando não configurados', function () {
        $this->actingAs($this->admin)
            ->putJson(route('regras.update', 'sinal'), ['ativo' => true, 'params' => []])
            ->assertOk();

        $servico = app(RegraService::class);
        expect($servico->param('sinal', 'percentual', null, $this->company->id))->toBe(30)
            ->and($servico->param('sinal', 'reembolsavel', null, $this->company->id))->toBeTrue();
    });

    it('desativar a regra invalida o cache imediatamente', function () {
        $catalogo = RegraCatalogo::where('codigo', 'no_show')->first();
        $regra = CompanyRegra::create([
            'company_id' => $this->company->id,
            'regra_catalogo_id' => $catalogo->id,
            'ativo' => true,
            'params' => [],
        ]);

        $servico = app(RegraService::class);
        expect($servico->enabled('no_show', $this->company->id))->toBeTrue();

        $regra->update(['ativo' => false]);
        expect($servico->enabled('no_show', $this->company->id))->toBeFalse();
    });

    it('regras não vazam entre empresas', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-regras', 'plano' => 'trial', 'ativo' => true]);
        $catalogo = RegraCatalogo::where('codigo', 'no_show')->first();
        CompanyRegra::create([
            'company_id' => $outra->id,
            'regra_catalogo_id' => $catalogo->id,
            'ativo' => true,
            'params' => [],
        ]);

        $servico = app(RegraService::class);
        expect($servico->enabled('no_show', $outra->id))->toBeTrue()
            ->and($servico->enabled('no_show', $this->company->id))->toBeFalse();
    });

    it('super_admin gerencia o catálogo via API', function () {
        $resposta = $this->actingAs($this->super)
            ->postJson(route('admin.regras.store'), [
                'codigo' => 'teste_regra',
                'nome' => 'Regra de Teste',
                'descricao' => 'Apenas teste',
                'categoria' => 'Geral',
                'ativo' => true,
                'params_schema' => [
                    ['key' => 'valor', 'label' => 'Valor', 'type' => 'number', 'min' => 0, 'max' => 10],
                ],
                'params_default' => ['valor' => 5],
            ])
            ->assertCreated()
            ->json();

        $this->actingAs($this->super)
            ->putJson(route('admin.regras.update', $resposta['id']), [
                'nome' => 'Regra Editada',
                'categoria' => 'Geral',
                'ativo' => true,
                'params_schema' => $resposta['params_schema'],
                'params_default' => $resposta['params_default'],
            ])
            ->assertOk()
            ->assertJsonPath('nome', 'Regra Editada');

        $this->actingAs($this->super)
            ->deleteJson(route('admin.regras.destroy', $resposta['id']))
            ->assertOk();

        expect(RegraCatalogo::where('codigo', 'teste_regra')->exists())->toBeFalse();
    });

    it('admin_empresa não acessa o catálogo do sistema', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.regras.index'))
            ->assertForbidden();
    });

    it('retenção LGPD usa o prazo da regra da empresa', function () {
        $cliente = Cliente::create([
            'company_id' => $this->company->id, 'name' => 'Antigo', 'phone' => '111',
        ]);
        Cliente::where('id', $cliente->id)->update(['created_at' => now()->subYears(3)]);

        // Sem regra ativa: nada acontece
        $this->artisan('lgpd:retencao')->assertSuccessful();
        expect($cliente->fresh()->anonymized_at)->toBeNull();

        // Com regra ativa (24 meses): anonimiza
        $this->actingAs($this->admin)
            ->putJson(route('regras.update', 'lgpd_retencao'), ['ativo' => true, 'params' => ['meses' => 24]])
            ->assertOk();
        auth()->logout();

        $this->artisan('lgpd:retencao')->assertSuccessful();
        expect(Cliente::withTrashed()->find($cliente->id)->anonymized_at)->not->toBeNull();
    });
});
