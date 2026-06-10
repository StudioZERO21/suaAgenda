<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Profissional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Foto Prof',
        'slug' => 'empresa-foto-prof',
        'plano' => 'trial',
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Carlos Prof',
        'ativo' => true,
    ]);
});

describe('profissional_foto', function () {
    it('admin pode fazer upload de foto', function () {
        $file = UploadedFile::fake()->image('foto.jpg', 200, 200);

        $response = $this->actingAs($this->admin)
            ->postJson(route('profissionais.foto.upload', $this->prof), ['foto' => $file]);

        $response->assertOk()->assertJsonStructure(['foto_url']);

        $this->prof->refresh();
        expect($this->prof->foto_path)->not->toBeNull();
        Storage::disk('public')->assertExists($this->prof->foto_path);
    });

    it('analista não pode fazer upload de foto', function () {
        $file = UploadedFile::fake()->image('foto.jpg');

        $this->actingAs($this->analista)
            ->postJson(route('profissionais.foto.upload', $this->prof), ['foto' => $file])
            ->assertForbidden();
    });

    it('admin pode remover foto', function () {
        $path = 'profissionais/'.$this->company->id.'/foto.jpg';
        Storage::disk('public')->put($path, 'img');
        $this->prof->update(['foto_path' => $path]);

        $this->actingAs($this->admin)
            ->deleteJson(route('profissionais.foto.delete', $this->prof))
            ->assertNoContent();

        Storage::disk('public')->assertMissing($path);
        expect($this->prof->fresh()->foto_path)->toBeNull();
    });

    it('upload substitui foto anterior', function () {
        $path1 = 'profissionais/'.$this->company->id.'/old.jpg';
        Storage::disk('public')->put($path1, 'old');
        $this->prof->update(['foto_path' => $path1]);

        $newFile = UploadedFile::fake()->image('new.jpg');
        $this->actingAs($this->admin)
            ->postJson(route('profissionais.foto.upload', $this->prof), ['foto' => $newFile])
            ->assertOk();

        Storage::disk('public')->assertMissing($path1);
        expect($this->prof->fresh()->foto_path)->not->toBe($path1);
    });

    it('foto aparece na view de profissionais', function () {
        $path = 'profissionais/'.$this->company->id.'/foto.jpg';
        Storage::disk('public')->put($path, 'img');
        $this->prof->update(['foto_path' => $path]);

        $this->actingAs($this->admin)
            ->get(route('profissionais.index'))
            ->assertOk()
            ->assertSee('object-fit:cover');
    });

    it('cannot upload to profissional of another company', function () {
        $outraCompany = Company::create([
            'name' => 'Outra',
            'slug' => 'outra-prof-foto',
            'plano' => 'trial',
            'ativo' => true,
        ]);
        $outraProf = Profissional::create(['company_id' => $outraCompany->id, 'name' => 'Outro', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->postJson(route('profissionais.foto.upload', $outraProf), [
                'foto' => UploadedFile::fake()->image('foto.jpg'),
            ])
            ->assertForbidden();
    });
});
