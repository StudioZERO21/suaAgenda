<?php

declare(strict_types=1);

use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->svc = new ImageService(maxSide: 1024, quality: 82);
});

describe('image_service', function () {
    it('reduz o maior lado para no máximo 1024px (paisagem)', function () {
        $path = $this->svc->store(UploadedFile::fake()->image('g.jpg', 2400, 1600), 'teste');

        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($path));
        expect(max($w, $h))->toBe(1024)
            ->and($h)->toBe((int) round(1600 * (1024 / 2400)));
    });

    it('reduz o maior lado para 1024px (retrato)', function () {
        $path = $this->svc->store(UploadedFile::fake()->image('p.jpg', 900, 2000), 'teste');

        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($path));
        expect(max($w, $h))->toBe(1024);
    });

    it('não amplia imagens menores que o limite', function () {
        $path = $this->svc->store(UploadedFile::fake()->image('s.jpg', 640, 480), 'teste');

        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($path));
        expect($w)->toBe(640)->and($h)->toBe(480);
    });

    it('preserva o formato png e mantém transparência', function () {
        $path = $this->svc->store(UploadedFile::fake()->image('logo.png', 1500, 1500), 'teste');

        expect($path)->toEndWith('.png');
        $info = getimagesizefromstring(Storage::disk('public')->get($path));
        expect($info['mime'])->toBe('image/png')
            ->and(max($info[0], $info[1]))->toBe(1024);
    });

    it('armazena com nome único no diretório informado', function () {
        $path = $this->svc->store(UploadedFile::fake()->image('x.jpg', 500, 500), 'profissionais/abc');

        expect($path)->toStartWith('profissionais/abc/');
        Storage::disk('public')->assertExists($path);
    });
});
