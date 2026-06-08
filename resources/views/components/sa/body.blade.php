@props(['padding' => '20px 32px 0'])

<div {{ $attributes->merge(['class' => 'sa-page-body', 'style' => "padding:{$padding}"]) }}>
    {{ $slot }}
</div>
