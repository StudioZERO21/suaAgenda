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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Banner', 'slug' => 'empresa-banner',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('site_upload_banner', function () {
    it('admin pode fazer upload do banner e recebe URL', function () {
        $file = UploadedFile::fake()->image('banner.jpg', 1920, 400);

        $this->actingAs($this->admin)
            ->postJson(route('site.upload.banner'), ['image' => $file])
            ->assertOk()
            ->assertJsonStructure(['url']);

        $path = $this->company->fresh()->settings['site']['banner_path'];
        expect($path)->not->toBeNull();
        Storage::disk('public')->assertExists($path);
    });

    it('path do banner é salvo nos settings da company', function () {
        $file = UploadedFile::fake()->image('banner2.png', 1920, 400);

        $this->actingAs($this->admin)
            ->postJson(route('site.upload.banner'), ['image' => $file]);

        $site = $this->company->fresh()->settings['site'];
        expect($site['banner_path'])->toContain('site_banners');
    });

    it('gestor pode fazer upload do banner', function () {
        $file = UploadedFile::fake()->image('banner-gestor.jpg', 1920, 400);

        $this->actingAs($this->gestor)
            ->postJson(route('site.upload.banner'), ['image' => $file])
            ->assertOk();
    });

    it('rejeita tipo de arquivo inválido', function () {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin)
            ->postJson(route('site.upload.banner'), ['image' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    });

    it('substitui banner anterior ao fazer novo upload', function () {
        $first = UploadedFile::fake()->image('banner-first.jpg');
        $this->actingAs($this->admin)
            ->postJson(route('site.upload.banner'), ['image' => $first]);
        $firstPath = $this->company->fresh()->settings['site']['banner_path'];

        $second = UploadedFile::fake()->image('banner-second.jpg');
        $this->actingAs($this->admin)
            ->postJson(route('site.upload.banner'), ['image' => $second]);
        $secondPath = $this->company->fresh()->settings['site']['banner_path'];

        expect($secondPath)->not->toBe($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    });

    it('analista não pode fazer upload do banner (sem cfg_site)', function () {
        $file = UploadedFile::fake()->image('banner-analista.jpg');

        $this->actingAs($this->analista)
            ->postJson(route('site.upload.banner'), ['image' => $file])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $file = UploadedFile::fake()->image('banner.jpg');
        $this->postJson(route('site.upload.banner'), ['image' => $file])
            ->assertUnauthorized();
    });
});
