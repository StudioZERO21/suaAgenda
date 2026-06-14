<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Export Prof',
        'slug' => 'empresa-export-prof',
        'plano' => 'trial',
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'João Silva',
        'especialidade' => 'Barbeiro',
        'comissao_pct' => 30.00,
        'ativo' => true,
    ]);
});

describe('profissional_export_csv', function () {
    it('admin pode exportar CSV', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('profissionais.exportar'));

        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('text/csv');
    });

    it('analista pode exportar CSV', function () {
        $this->actingAs($this->analista)
            ->get(route('profissionais.exportar'))
            ->assertOk();
    });

    it('CSV contém cabeçalhos corretos', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('profissionais.exportar'));

        $content = $response->streamedContent();
        expect($content)->toContain('Nome')
            ->and($content)->toContain('Especialidade')
            ->and($content)->toContain('Comissão');
    });

    it('CSV contém dados do profissional', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('profissionais.exportar'));

        $content = $response->streamedContent();
        expect($content)->toContain('João Silva')
            ->and($content)->toContain('Barbeiro')
            ->and($content)->toContain('Ativo');
    });

    it('isolamento: não exporta profissionais de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-exp-prof', 'plano' => 'trial', 'ativo' => true]);
        Profissional::create(['company_id' => $outra->id, 'name' => 'Intruso', 'ativo' => true]);

        $content = $this->actingAs($this->admin)
            ->get(route('profissionais.exportar'))
            ->streamedContent();

        expect($content)->not->toContain('Intruso');
    });

    it('unauthenticated é redirecionado', function () {
        $this->get(route('profissionais.exportar'))->assertRedirect();
    });
});

describe('profissional_export_pdf', function () {
    it('admin pode exportar PDF', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('profissionais.exportar.pdf'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        expect(str_starts_with($response->getContent(), '%PDF'))->toBeTrue();
    });

    it('PDF contém nome do profissional', function () {
        $this->actingAs($this->admin)
            ->get(route('profissionais.exportar.pdf'))
            ->assertOk();
    });

    it('não exporta profissionais de outra empresa no PDF', function () {
        $outra = Company::create(['name' => 'Outra PDF', 'slug' => 'outra-pdf-prof', 'plano' => 'trial', 'ativo' => true]);
        Profissional::create(['company_id' => $outra->id, 'name' => 'Intruso PDF', 'ativo' => true]);

        $html = view('profissionais.export-pdf', [
            'profissionais' => Profissional::where('company_id', $this->company->id)->withCount('agendamentos')->with('servicos:id,nome')->get(),
            'company' => $this->company,
        ])->render();

        expect($html)->not->toContain('Intruso PDF');
    });
});
