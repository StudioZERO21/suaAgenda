<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia SiteBanner', 'slug' => 'barbearia-site-banner',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('site_remove_banner', function () {
    it('admin remove banner existente e limpa path nas settings', function () {
        Storage::disk('public')->put('site_banners/fake.jpg', 'fake_content');

        $settings = ['site' => ['banner_path' => 'site_banners/fake.jpg']];
        $this->company->update(['settings' => $settings]);

        $data = $this->actingAs($this->admin)
            ->deleteJson(route('site.remove.banner'))
            ->assertOk()
            ->json();

        expect($data['success'])->toBeTrue();
        Storage::disk('public')->assertMissing('site_banners/fake.jpg');

        $this->company->refresh();
        expect($this->company->settings['site']['banner_path'])->toBeNull();
    });

    it('remove banner sem arquivo físico não lança erro', function () {
        $settings = ['site' => ['banner_path' => 'site_banners/inexistente.jpg']];
        $this->company->update(['settings' => $settings]);

        $this->actingAs($this->admin)
            ->deleteJson(route('site.remove.banner'))
            ->assertOk();
    });

    it('remove banner quando não há banner definido', function () {
        $this->actingAs($this->admin)
            ->deleteJson(route('site.remove.banner'))
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('gestor pode remover banner', function () {
        $this->actingAs($this->gestor)
            ->deleteJson(route('site.remove.banner'))
            ->assertOk();
    });

    it('analista não pode remover banner (sem cfg_site)', function () {
        $this->actingAs($this->analista)
            ->deleteJson(route('site.remove.banner'))
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->deleteJson(route('site.remove.banner'))
            ->assertUnauthorized();
    });
});
