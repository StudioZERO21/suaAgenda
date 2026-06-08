@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
])

@php
    $sizes = [
        'sm' => 'sa-btn--sm',
        'md' => 'sa-btn--md',
        'lg' => 'sa-btn--lg',
    ];
    $variants = [
        'primary'   => 'sa-btn--primary',
        'secondary' => 'sa-btn--secondary',
        'ghost'     => 'sa-btn--ghost',
        'danger'    => 'sa-btn--danger',
        'muted'     => 'sa-btn--muted',
    ];
    $classes = 'sa-btn ' . ($sizes[$size] ?? $sizes['md']) . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)<span class="sa-btn__icon">{!! $icon !!}</span>@endif
    {{ $slot }}
</a>
@else
<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)<span class="sa-btn__icon">{!! $icon !!}</span>@endif
    {{ $slot }}
</button>
@endif
