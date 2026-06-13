<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Otimização de imagens (GD): redimensiona o maior lado para no máximo
 * {maxSide}px e recomprime, reduzindo o espaço em disco sem perda visível
 * de qualidade. Preserva o formato (e a transparência de PNG/WebP).
 */
class ImageService
{
    public function __construct(
        private readonly int $maxSide = 1024,
        private readonly int $quality = 82,
    ) {}

    /**
     * Otimiza e armazena a imagem enviada, retornando o caminho.
     * Faz fallback para o armazenamento normal se não for processável.
     */
    public function store(UploadedFile $file, string $dir, string $disk = 'public'): string
    {
        $dir = trim($dir, '/');

        $binary = $this->optimizeBinary((string) file_get_contents($file->getRealPath()));

        if ($binary === null) {
            return $file->store($dir, $disk);
        }

        $ext = $this->extensionFor((string) file_get_contents($file->getRealPath()));
        $path = $dir.'/'.Str::uuid()->toString().'.'.$ext;

        Storage::disk($disk)->put($path, $binary);

        return $path;
    }

    /**
     * Reprocessa um arquivo já existente no disco, sobrescrevendo no mesmo
     * caminho. Só regrava se o resultado for menor que o original (evita
     * crescer ou degradar imagens já otimizadas — idempotente na prática).
     *
     * @return array{status:string, antes:int, depois:int}
     */
    public function reprocessar(string $path, string $disk = 'public'): array
    {
        $store = Storage::disk($disk);

        if (! $store->exists($path)) {
            return ['status' => 'ausente', 'antes' => 0, 'depois' => 0];
        }

        $original = (string) $store->get($path);
        $antes = strlen($original);

        $otimizado = $this->optimizeBinary($original);

        if ($otimizado === null) {
            return ['status' => 'ignorado', 'antes' => $antes, 'depois' => $antes];
        }

        $depois = strlen($otimizado);

        if ($depois >= $antes) {
            return ['status' => 'ja-otimizado', 'antes' => $antes, 'depois' => $antes];
        }

        $store->put($path, $otimizado);

        return ['status' => 'otimizado', 'antes' => $antes, 'depois' => $depois];
    }

    public function optimize(UploadedFile $file): ?string
    {
        return $this->optimizeBinary((string) file_get_contents($file->getRealPath()));
    }

    /**
     * Núcleo: recebe o binário da imagem e devolve o binário otimizado
     * (ou null se não for possível processar com o GD).
     */
    public function optimizeBinary(string $contents): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $info = @getimagesizefromstring($contents);
        if ($info === false) {
            return null;
        }

        [$width, $height] = $info;
        $mime = $info['mime'] ?? 'image/jpeg';

        $src = @imagecreatefromstring($contents);
        if (! $src) {
            return null;
        }

        $maior = max($width, $height);
        $escala = $maior > $this->maxSide ? $this->maxSide / $maior : 1.0;
        $novoW = max(1, (int) round($width * $escala));
        $novoH = max(1, (int) round($height * $escala));

        $dst = imagecreatetruecolor($novoW, $novoH);

        if (in_array($mime, ['image/png', 'image/webp'], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparente = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $novoW, $novoH, $transparente);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $novoW, $novoH, $width, $height);

        ob_start();
        $this->writer($mime)($dst);
        $binary = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $binary !== false ? $binary : null;
    }

    private function extensionFor(string $contents): string
    {
        $info = @getimagesizefromstring($contents);

        return match ($info['mime'] ?? null) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
    }

    private function writer(string $mime): callable
    {
        return match ($mime) {
            'image/png' => fn ($img) => imagepng($img, null, 6),
            'image/webp' => fn ($img) => imagewebp($img, null, $this->quality),
            'image/gif' => fn ($img) => imagegif($img),
            default => fn ($img) => imagejpeg($img, null, $this->quality),
        };
    }
}
