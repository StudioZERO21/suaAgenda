@props(['href' => null, 'title' => '', 'danger' => false, 'type' => 'button'])

@php
    $class = 'sa-icon-btn' . ($danger ? ' sa-icon-btn--danger' : '');
@endphp

@if($href)
<a href="{{ $href }}" title="{{ $title }}" {{ $attributes->merge(['class' => $class]) }}>
    {{ $slot }}
</a>
@else
<button type="{{ $type }}" title="{{ $title }}" {{ $attributes->merge(['class' => $class]) }}>
    {{ $slot }}
</button>
@endif
