@props(['name', 'size' => 34, 'color' => 'var(--sa-side-accent)'])

@php
    $parts = explode(' ', trim($name));
    $initials = strtoupper(substr($parts[0] ?? '', 0, 1)) . strtoupper(substr($parts[1] ?? '', 0, 1));
    $fontSize = round($size * 0.38);
@endphp

<div {{ $attributes->merge([
    'class' => 'sa-avatar-inline',
    'style' => "width:{$size}px;height:{$size}px;font-size:{$fontSize}px;background:{$color}",
]) }}>
    {{ $initials ?: '?' }}
</div>
