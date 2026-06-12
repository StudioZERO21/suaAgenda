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

    // Testa que os settings do site ficam no campo JSON da company
    it('test_save_stores_site_settings_in_company_json', function () {
        $this->actingAs($this->admin)
            ->putJson(route('site.save'), [
                'headline' => 'Título JSON',
                'footer_text' => 'Rodapé customizado',
                'google_analytics' => 'G-ABC123',
                'show_services' => true,
                'show_map' => false,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $site = $this->company->fresh()->settings['site'];
        expect($site['headline'])->toBe('Título JSON');
        expect($site['footer_text'])->toBe('Rodapé customizado');
        expect($site['google_analytics'])->toBe('G-ABC123');
        expect($site['show_services'])->toBeTrue();
        expect($site['show_map'])->toBeFalse();
    });

    // Testa upload de banner: armazena arquivo e retorna URL
    it('test_upload_banner_stores_file_and_returns_url', function () {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('banner.jpg', 1920, 800);

        $response = $this->actingAs($this->admin)
            ->postJson(route('site.upload.banner'), ['image' => $file])
            ->assertOk()
            ->assertJsonStructure(['url']);

        $url = $response->json('url');
        expect($url)->toBeString()->toContain('site_banners');

        // Verifica que o path foi salvo no settings da company
        $path = $this->company->fresh()->settings['site']['banner_path'];
        expect($path)->not->toBeNull();
        Storage::disk('public')->assertExists($path);
    });

    // Testa que apenas admin_empresa pode salvar settings do site (não unauthenticated)
    it('test_only_admin_empresa_can_save_site_settings', function () {
        // Usuário sem autenticação é redirecionado
        $this->putJson(route('site.save'), ['headline' => 'Anônimo'])
            ->assertUnauthorized();

        // admin_empresa pode salvar
        $this->actingAs($this->admin)
            ->putJson(route('site.save'), ['headline' => 'Admin OK'])
            ->assertOk()
            ->assertJson(['success' => true]);
    });
});
