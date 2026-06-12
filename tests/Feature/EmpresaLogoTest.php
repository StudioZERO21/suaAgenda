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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Logo Teste',
        'slug' => 'empresa-logo-teste',
        'plano' => 'trial',
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('empresa_logo', function () {
    it('admin pode fazer upload do logo', function () {
        $file = UploadedFile::fake()->image('logo.png', 400, 400);

        $response = $this->actingAs($this->admin)
            ->postJson(route('configuracoes.empresa.logo.upload'), ['logo' => $file]);

        $response->assertOk()->assertJsonStructure(['logo_url']);

        $this->company->refresh();
        expect($this->company->logo_path)->not->toBeNull();
        Storage::disk('public')->assertExists($this->company->logo_path);
    });

    it('analista não pode fazer upload do logo', function () {
        $this->actingAs($this->analista)
            ->postJson(route('configuracoes.empresa.logo.upload'), [
                'logo' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertForbidden();
    });

    it('upload substitui logo anterior', function () {
        $path1 = 'logos/'.$this->company->id.'/old.png';
        Storage::disk('public')->put($path1, 'old');
        $this->company->update(['logo_path' => $path1]);

        $this->actingAs($this->admin)
            ->postJson(route('configuracoes.empresa.logo.upload'), [
                'logo' => UploadedFile::fake()->image('new.png'),
            ])
            ->assertOk();

        Storage::disk('public')->assertMissing($path1);
        expect($this->company->fresh()->logo_path)->not->toBe($path1);
    });

    it('admin pode remover logo', function () {
        $path = 'logos/'.$this->company->id.'/logo.png';
        Storage::disk('public')->put($path, 'img');
        $this->company->update(['logo_path' => $path]);

        $this->actingAs($this->admin)
            ->deleteJson(route('configuracoes.empresa.logo.delete'))
            ->assertNoContent();

        Storage::disk('public')->assertMissing($path);
        expect($this->company->fresh()->logo_path)->toBeNull();
    });

    it('logo aparece na página de configuração', function () {
        $path = 'logos/'.$this->company->id.'/logo.png';
        Storage::disk('public')->put($path, 'img');
        $this->company->update(['logo_path' => $path]);

        $this->actingAs($this->admin)
            ->get(route('configuracoes.empresa'))
            ->assertOk()
            ->assertSee('object-fit:cover');
    });

    it('rejeita arquivo maior que 2MB', function () {
        $file = UploadedFile::fake()->create('logo.png', 3000, 'image/png');

        $this->actingAs($this->admin)
            ->postJson(route('configuracoes.empresa.logo.upload'), ['logo' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['logo']);
    });
});
