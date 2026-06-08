@props(['label', 'sub' => null])

<div class="sa-setting-row">
    <div class="sa-setting-row__text">
        <div class="sa-setting-row__label">{{ $label }}</div>
        @if($sub)<div class="sa-setting-row__sub">{{ $sub }}</div>@endif
    </div>
    <div class="sa-setting-row__action">{{ $slot }}</div>
</div>
