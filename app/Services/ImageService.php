<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Otimização de imagens no upload (GD): redimensiona o maior lado para no
 * máximo {maxSide}px e recomprime, reduzindo o espaço em disco sem perda
 * visível de qualidade. Preserva o formato (e a transparência de PNG/WebP).
 */
class ImageService
{
    public function __construct(
        private readonly int $maxSide = 1024,
        private readonly int $quality = 82,
    ) {}

    /**
     * Otimiza e armazena a imagem no disco indicado, retornando o caminho.
     * Se o GD não estiver disponível ou o arquivo não for imagem suportada,
     * faz fallback para o armazenamento normal (sem otimizar).
     */
    public function store(UploadedFile $file, string $dir, string $disk = 'public'): string
    {
        $dir = trim($dir, '/');

        $binary = $this->optimize($file);

        if ($binary === null) {
            // Fallback seguro: guarda o original.
            return $file->store($dir, $disk);
        }

        [$ext] = $this->targetFormat($file);
        $path = $dir.'/'.Str::uuid()->toString().'.'.$ext;

        Storage::disk($disk)->put($path, $binary);

        return $path;
    }

    /**
     * Gera o binário otimizado (ou null se não for possível processar).
     */
    public function optimize(UploadedFile $file): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $info = @getimagesize($file->getRealPath());
        if ($info === false) {
            return null;
        }

        [$width, $height] = $info;
        $mime = $info['mime'] ?? $file->getMimeType();

        $src = $this->createFromFile($file->getRealPath(), $mime);
        if ($src === null) {
            return null;
        }

        $maior = max($width, $height);
        $escala = $maior > $this->maxSide ? $this->maxSide / $maior : 1.0;
        $novoW = max(1, (int) round($width * $escala));
        $novoH = max(1, (int) round($height * $escala));

        $dst = imagecreatetruecolor($novoW, $novoH);

        // Preserva transparência (PNG/WebP).
        if (in_array($mime, ['image/png', 'image/webp'], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparente = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $novoW, $novoH, $transparente);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $novoW, $novoH, $width, $height);

        ob_start();
        [, $writer] = $this->targetFormat(null, $mime);
        $writer($dst);
        $binary = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $binary !== false ? $binary : null;
    }

    /**
     * @return array{0:string,1:callable} [extensão, escritor GD]
     */
    private function targetFormat(?UploadedFile $file, ?string $mime = null): array
    {
        $mime ??= $file?->getMimeType();

        return match ($mime) {
            'image/png' => ['png', fn ($img) => imagepng($img, null, 6)],
            'image/webp' => ['webp', fn ($img) => imagewebp($img, null, $this->quality)],
            'image/gif' => ['gif', fn ($img) => imagegif($img)],
            default => ['jpg', fn ($img) => imagejpeg($img, null, $this->quality)],
        };
    }

    private function createFromFile(string $path, string $mime): ?\GdImage
    {
        $img = match ($mime) {
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            default => false,
        };

        return $img ?: null;
    }
}
