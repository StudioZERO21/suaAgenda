@props(['name' => 'servico_generico', 'size' => 16, 'color' => 'currentColor'])

@php
    use App\Support\SaServiceIcons;
    $assetUrl = SaServiceIcons::assetUrl($name);
    $path = SaServiceIcons::path($name);
@endphp

@if($assetUrl)
<img src="{{ $assetUrl }}" width="{{ $size }}" height="{{ $size }}" alt=""
     {{ $attributes->merge(['style' => 'display:block;object-fit:contain;width:'.$size.'px;height:'.$size.'px']) }}>
@else
<svg {{ $attributes->merge(['width' => $size, 'height' => $size, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => $color, 'stroke-width' => '2', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round']) }}>
    {!! $path !!}
</svg>
@endif
