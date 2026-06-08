@props(['paddingBottom' => '36px'])

<div {{ $attributes->merge(['style' => "flex:1;padding:0 0 {$paddingBottom}"]) }}>
    {{ $slot }}
</div>
