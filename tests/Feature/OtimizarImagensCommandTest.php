<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Profissional;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function imagemGrandeJpeg(int $w = 2000, int $h = 1500): string
{
    $img = imagecreatetruecolor($w, $h);
    // Algum ruído para o JPEG não comprimir a quase nada.
    for ($i = 0; $i < 4000; $i++) {
        imagesetpixel($img, random_int(0, $w - 1), random_int(0, $h - 1), random_int(0, 16777215));
    }
    ob_start();
    imagejpeg($img, null, 95);
    $bin = ob_get_clean();
    imagedestroy($img);

    return $bin;
}

beforeEach(function () {
    Storage::fake('public');
    $this->company = Company::create(['name' => 'Empresa Img', 'slug' => 'empresa-img', 'plano' => 'trial', 'ativo' => true]);
});

describe('images_otimizar', function () {
    it('reduz dimensões e tamanho de imagens existentes do acervo', function () {
        $bin = imagemGrandeJpeg(2000, 1500);
        Storage::disk('public')->put('profissionais/foto.jpg', $bin);

        Profissional::create([
            'company_id' => $this->company->id, 'name' => 'Carlos',
            'ativo' => true, 'foto_path' => 'profissionais/foto.jpg',
        ]);

        $antes = strlen(Storage::disk('public')->get('profissionais/foto.jpg'));

        $this->artisan('images:otimizar')->assertSuccessful();

        $depoisBin = Storage::disk('public')->get('profissionais/foto.jpg');
        [$w, $h] = getimagesizefromstring($depoisBin);

        expect(max($w, $h))->toBe(1024)
            ->and(strlen($depoisBin))->toBeLessThan($antes);
    });

    it('dry-run não altera os arquivos', function () {
        $bin = imagemGrandeJpeg(1800, 1200);
        Storage::disk('public')->put('profissionais/x.jpg', $bin);
        Profissional::create([
            'company_id' => $this->company->id, 'name' => 'Ana',
            'ativo' => true, 'foto_path' => 'profissionais/x.jpg',
        ]);

        $this->artisan('images:otimizar --dry-run')->assertSuccessful();

        expect(Storage::disk('public')->get('profissionais/x.jpg'))->toBe($bin);
    });

    it('é idempotente: segunda execução não reduz mais', function () {
        Storage::disk('public')->put('profissionais/y.jpg', imagemGrandeJpeg(2400, 1200));
        Profissional::create([
            'company_id' => $this->company->id, 'name' => 'Bia',
            'ativo' => true, 'foto_path' => 'profissionais/y.jpg',
        ]);

        $this->artisan('images:otimizar')->assertSuccessful();
        $apos1 = Storage::disk('public')->get('profissionais/y.jpg');

        $this->artisan('images:otimizar')->assertSuccessful();
        $apos2 = Storage::disk('public')->get('profissionais/y.jpg');

        expect($apos2)->toBe($apos1);
    });

    it('roda sem erro quando não há imagens', function () {
        $this->artisan('images:otimizar')->assertSuccessful();
    });
});
