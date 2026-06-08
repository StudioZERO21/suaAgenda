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

    $this->company = Company::create([
        'name' => 'Empresa Site',
        'slug' => 'empresa-site',
        'plano' => 'trial',
        'whatsapp' => '(11) 99999-0020',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');
});

describe('site settings', function () {
    it('admin pode carregar a página de configurações do site', function () {
        $this->actingAs($this->admin)
            ->get(route('site.index'))
            ->assertOk();
    });

    it('admin pode salvar configurações do site e recebe json 200', function () {
        $this->actingAs($this->admin)
            ->putJson(route('site.save'), [
                'headline' => 'Novo título',
                'subheadline' => 'Nova descrição',
                'show_stats' => false,
                'meta_title' => 'Meta novo',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $settings = $this->company->fresh()->settings;
        expect($settings['site']['headline'])->toBe('Novo título');
        expect($settings['site']['show_stats'])->toBeFalse();
    });

    it('gestor pode salvar configurações do site', function () {
        $this->actingAs($this->gestor)
            ->putJson(route('site.save'), ['headline' => 'Gestor headline'])
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('validação rejeita headline muito longa', function () {
        $this->actingAs($this->admin)
            ->putJson(route('site.save'), ['headline' => str_repeat('x', 256)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['headline']);
    });

    it('salva múltiplas vezes sem sobrescrever outras chaves de settings', function () {
        $this->company->update(['settings' => ['theme_palette' => 'B', 'site' => ['headline' => 'Original']]]);

        $this->actingAs($this->admin)
            ->putJson(route('site.save'), ['headline' => 'Atualizado', 'meta_title' => 'Meta'])
            ->assertOk();

        $settings = $this->company->fresh()->settings;
        expect($settings['site']['headline'])->toBe('Atualizado');
        expect($settings['theme_palette'])->toBe('B');
    });
});
