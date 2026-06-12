<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa OG', 'slug' => 'empresa-og',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');
});

describe('site_upload_og', function () {
    it('admin pode fazer upload da imagem OG e recebe URL', function () {
        $file = UploadedFile::fake()->image('og.jpg', 1200, 630);

        $this->actingAs($this->admin)
            ->postJson(route('site.upload.og'), ['image' => $file])
            ->assertOk()
            ->assertJsonStructure(['url']);

        $path = $this->company->fresh()->settings['site']['og_image'];
        expect($path)->not->toBeNull();
        Storage::disk('public')->assertExists($path);
    });

    it('path da imagem OG é salvo nos settings da company', function () {
        $file = UploadedFile::fake()->image('og2.png', 1200, 630);

        $this->actingAs($this->admin)
            ->postJson(route('site.upload.og'), ['image' => $file]);

        $site = $this->company->fresh()->settings['site'];
        expect($site['og_image'])->toContain('site_og');
    });

    it('gestor pode fazer upload da imagem OG', function () {
        $file = UploadedFile::fake()->image('og-gestor.jpg', 1200, 630);

        $this->actingAs($this->gestor)
            ->postJson(route('site.upload.og'), ['image' => $file])
            ->assertOk()
            ->assertJsonStructure(['url']);
    });

    it('rejeita tipo de arquivo inválido', function () {
        $file = UploadedFile::fake()->create('script.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin)
            ->postJson(route('site.upload.og'), ['image' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    });

    it('substitui imagem OG anterior ao fazer novo upload', function () {
        // First upload
        $first = UploadedFile::fake()->image('og-first.jpg');
        $this->actingAs($this->admin)
            ->postJson(route('site.upload.og'), ['image' => $first]);
        $firstPath = $this->company->fresh()->settings['site']['og_image'];

        // Second upload replaces first
        $second = UploadedFile::fake()->image('og-second.jpg');
        $this->actingAs($this->admin)
            ->postJson(route('site.upload.og'), ['image' => $second]);
        $secondPath = $this->company->fresh()->settings['site']['og_image'];

        expect($secondPath)->not->toBe($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    });

    it('unauthenticated é rejeitado', function () {
        $file = UploadedFile::fake()->image('og.jpg');
        $this->postJson(route('site.upload.og'), ['image' => $file])
            ->assertUnauthorized();
    });
});

describe('site_remove_banner', function () {
    it('admin pode remover o banner e path é zerado', function () {
        $this->company->update([
            'settings' => ['site' => ['banner_path' => 'site_banners/test/banner.jpg']],
        ]);
        Storage::disk('public')->put('site_banners/test/banner.jpg', 'fake');

        $this->actingAs($this->admin)
            ->deleteJson(route('site.remove.banner'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $path = $this->company->fresh()->settings['site']['banner_path'];
        expect($path)->toBeNull();
    });

    it('remover banner sem banner existente retorna sucesso', function () {
        $this->actingAs($this->admin)
            ->deleteJson(route('site.remove.banner'))
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('unauthenticated é rejeitado ao remover banner', function () {
        $this->deleteJson(route('site.remove.banner'))
            ->assertUnauthorized();
    });
});
