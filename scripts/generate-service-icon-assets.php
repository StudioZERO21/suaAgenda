<?php

declare(strict_types=1);

/**
 * Gera SVGs pictográficos (estilo Flaticon, preto sólido) em public/assets/icons/servicos/.
 * Para substituir por ícones do Flaticon: baixe SVG preto, renomeie para {key}.svg e salve na mesma pasta.
 * Executar: php scripts/generate-service-icon-assets.php
 */

$dir = dirname(__DIR__).'/public/assets/icons/servicos';

if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
    fwrite(STDERR, "Não foi possível criar {$dir}\n");
    exit(1);
}

/** @var array<string, string> */
$icons = [
    'servico_generico' => <<<'SVG'
<path d="M256 48 96 128v256l160 80 160-80V128L256 48zm0 48 112 56v168l-112 56-112-56V152l112-56z"/>
<path d="M256 144v224M144 200l224 112M368 200 144 312"/>
SVG,
    'barber_pole' => <<<'SVG'
<rect x="200" y="64" width="112" height="384" rx="16" fill="#1a1a1a"/>
<path fill="#fff" d="M200 96h112v64H200zm0 128h112v64H200zm0 128h112v64H200z"/>
<path fill="#1a1a1a" d="m312 128-112 64 112 64V128zm0 128-112 64 112 64V256z"/>
<ellipse cx="256" cy="48" rx="40" ry="24" fill="#1a1a1a"/>
<ellipse cx="256" cy="464" rx="48" ry="28" fill="#1a1a1a"/>
SVG,
    'navalha' => <<<'SVG'
<path d="M96 384c0-80 64-144 144-144h32l160-160 32 32-160 160v32c0 80-64 144-144 144H96v-64z"/>
<path d="M352 112 400 64l48 48-48 48z"/>
<circle cx="128" cy="384" r="24"/>
SVG,
    'razor' => <<<'SVG'
<path d="M128 320h256c35 0 64-29 64-64s-29-64-64-64H128c-35 0-64 29-64 64s29 64 64 64z"/>
<path d="M96 256h320" stroke="#fff" stroke-width="16" fill="none"/>
<rect x="384" y="224" width="48" height="64" rx="8"/>
SVG,
    'barba_silhueta' => <<<'SVG'
<path d="M160 224c0-53 43-96 96-96s96 43 96 96v32c0 53-43 96-96 96h-32c-53 0-96-43-96-96v-32z"/>
<path d="M192 288c16 32 48 48 64 48s48-16 64-48c-16 16-40 24-64 24s-48-8-64-24z"/>
<path d="M208 208c16-8 32-12 48-12s32 4 48 12c-12 8-28 12-48 12s-36-4-48-12z"/>
SVG,
    'barba' => <<<'SVG'
<circle cx="256" cy="192" r="80"/>
<path d="M176 224c0 88 36 144 80 144s80-56 80-144c-24 32-56 48-80 48s-56-16-80-48z"/>
<ellipse cx="256" cy="176" rx="48" ry="32" fill="#fff"/>
<path d="M208 176c8-8 20-12 48-12s40 4 48 12"/>
SVG,
    'bigode' => <<<'SVG'
<path d="M160 288c24-32 48-40 96-40s72 8 96 40c-20 24-52 36-96 36s-76-12-96-36z"/>
<path d="M192 288c12 16 28 24 64 24s52-8 64-24"/>
<path d="M224 272c8 8 16 12 32 12s24-4 32-12"/>
SVG,
    'barba_tesoura' => <<<'SVG'
<circle cx="192" cy="176" r="64"/>
<path d="M128 208c0 72 28 112 64 112s64-40 64-112"/>
<path d="M320 320 416 416M416 320 320 416"/>
<circle cx="352" cy="352" r="24"/>
<circle cx="384" cy="384" r="24"/>
SVG,
    'rosto_barba' => <<<'SVG'
<ellipse cx="256" cy="208" rx="96" ry="112"/>
<path d="M176 256c0 80 36 128 80 128s80-48 80-128"/>
<ellipse cx="256" cy="192" rx="56" ry="72" fill="#fff"/>
<circle cx="224" cy="176" r="8" fill="#1a1a1a"/>
<circle cx="288" cy="176" r="8" fill="#1a1a1a"/>
SVG,
    'scissors' => <<<'SVG'
<circle cx="160" cy="352" r="48"/>
<circle cx="352" cy="352" r="48"/>
<path d="M192 320 320 192M320 320 448 192"/>
<path d="M192 384 320 256M320 384 448 256" stroke="#fff" stroke-width="12" fill="none"/>
SVG,
    'tesoura_pente' => <<<'SVG'
<path d="M96 128h96v288H96z"/>
<path d="M112 128h8v288h-8zm16 0h8v288h-8zm16 0h8v288h-8zm16 0h8v288h-8zm16 0h8v288h-8zm16 0h8v288h-8zm16 0h8v288h-8z"/>
<circle cx="352" cy="352" r="40"/>
<circle cx="416" cy="416" r="40"/>
<path d="M320 320 256 256M352 352 288 288"/>
SVG,
    'maquina_corte' => <<<'SVG'
<path d="M128 192h256c44 0 80 36 80 80v48c0 44-36 80-80 80H128c-44 0-80-36-80-80v-48c0-44 36-80 80-80z"/>
<rect x="160" y="224" width="192" height="96" rx="12" fill="#fff"/>
<path d="M384 256h64l32 48-32 48h-64z"/>
<circle cx="208" cy="272" r="12" fill="#1a1a1a"/>
<circle cx="272" cy="272" r="12" fill="#1a1a1a"/>
SVG,
    'comb' => <<<'SVG'
<path d="M160 128h192v64H160z"/>
<path d="M176 192v224h16V192zm32 0v224h16V192zm32 0v224h16V192zm32 0v224h16V192zm32 0v224h16V192zm32 0v224h16V192z"/>
SVG,
    'mulher_corte' => <<<'SVG'
<ellipse cx="240" cy="192" rx="72" ry="88"/>
<path d="M176 224c0 96 28 160 64 160s64-64 64-160"/>
<path d="M320 128 384 64M352 96 416 32"/>
<circle cx="384" cy="64" r="16"/>
<circle cx="416" cy="32" r="16"/>
SVG,
    'mulher_perfil' => <<<'SVG'
<path d="M320 128c-48-32-96-16-128 32-32 48-48 128-48 192 0 64 16 96 48 96s80-32 128-96V128z"/>
<path d="M192 352c32 48 80 64 128 32"/>
<ellipse cx="288" cy="176" rx="24" ry="32" fill="#fff"/>
SVG,
    'mulher_brilho' => <<<'SVG'
<ellipse cx="256" cy="224" rx="96" ry="112"/>
<path d="M176 256c0 96 36 160 80 160s80-64 80-160"/>
<path d="M128 96 144 128 176 144 144 160 128 192 112 160 80 144 112 128z"/>
<path d="M384 80 392 104 416 112 392 120 384 144 376 120 352 112 376 104z"/>
<path d="M416 192 424 208 440 216 424 224 416 240 408 224 392 216 408 208z"/>
SVG,
    'mulher_coloracao' => <<<'SVG'
<ellipse cx="224" cy="208" rx="80" ry="96"/>
<path d="M160 240c0 88 28 144 64 144s64-56 64-144"/>
<rect x="320" y="128" width="96" height="128" rx="12"/>
<path d="M336 144h64v32H336z" fill="#fff"/>
<path d="M368 256v96l-32 48"/>
SVG,
    'mulher_progressiva' => <<<'SVG'
<ellipse cx="256" cy="192" rx="80" ry="96"/>
<path d="M192 224v160M224 208v192M256 192v224M288 208v192M320 224v160"/>
<path d="M176 384c32 32 64 48 80 48s48-16 80-48"/>
SVG,
    'mulher_penteado' => <<<'SVG'
<ellipse cx="256" cy="208" rx="88" ry="104"/>
<path d="M176 240c0 72 16 128 32 160 24-16 48-24 48-24s24 8 48 24c16-32 32-88 32-160"/>
<path d="M208 128c16-32 40-48 48-48s32 16 48 48"/>
SVG,
    'mulher_cacheado' => <<<'SVG'
<ellipse cx="256" cy="208" rx="96" ry="112"/>
<path d="M176 240c0 96 36 160 80 160s80-64 80-160"/>
<path d="M192 176c16-24 32-32 64-32s48 8 64 32c-8 16-24 24-64 24s-56-8-64-24z"/>
<circle cx="208" cy="192" r="16"/>
<circle cx="256" cy="176" r="16"/>
<circle cx="304" cy="192" r="16"/>
SVG,
    'mulher_mascara' => <<<'SVG'
<ellipse cx="224" cy="208" rx="72" ry="88"/>
<path d="M160 240c0 88 28 144 64 144s64-56 64-144"/>
<rect x="320" y="160" width="96" height="112" rx="16"/>
<path d="M336 176h64v16H336z" fill="#fff"/>
<path d="M368 272v80"/>
SVG,
    'shampoo_salao' => <<<'SVG'
<path d="M224 96h64v32h-64z"/>
<path d="M208 128h96c17 0 32 15 32 32v224c0 17-15 32-32 32h-96c-17 0-32-15-32-32V160c0-17 15-32 32-32z"/>
<rect x="240" y="176" width="32" height="96" rx="8" fill="#fff"/>
SVG,
    'secador' => <<<'SVG'
<ellipse cx="192" cy="256" rx="96" ry="80"/>
<path d="M288 256h160l32 48-32 48H288z"/>
<path d="M128 256c0-44 28-80 64-80s64 36 64 80"/>
<circle cx="416" cy="256" r="16" fill="#fff"/>
SVG,
    'escova_capilar' => <<<'SVG'
<ellipse cx="256" cy="288" rx="80" ry="96"/>
<rect x="240" y="96" width="32" height="128" rx="8"/>
<path d="M176 320h160M192 352h128M208 384h96"/>
SVG,
    'palette' => <<<'SVG'
<path d="M256 96c-88 0-160 72-160 160 0 53 43 96 96 96 24 0 48-8 64-24 16 16 40 24 64 24 53 0 96-43 96-96 0-88-72-160-160-160z"/>
<circle cx="192" cy="192" r="24" fill="#fff"/>
<circle cx="256" cy="160" r="24" fill="#fff"/>
<circle cx="320" cy="192" r="24" fill="#fff"/>
<circle cx="288" cy="256" r="24" fill="#fff"/>
SVG,
];

$written = 0;

foreach ($icons as $key => $body) {
    $svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="#1a1a1a" aria-hidden="true">
{$body}
</svg>

SVG;
    $path = $dir.'/'.$key.'.svg';
    file_put_contents($path, $svg);
    $written++;
}

echo "Gerados {$written} ícones em {$dir}\n";
