@props(['label', 'value', 'sub' => null, 'trend' => null, 'positive' => true, 'icon' => null, 'accent' => 'var(--sa-primary)'])

<div class="sa-tint-card" style="--tint:{{ $accent }}">
    <div class="sa-tint-card__label">{{ $label }}</div>
    <div class="sa-tint-card__value">{{ $value }}</div>
    @if($trend)
    <div class="sa-tint-card__trend" style="color:{{ $positive ? '#10b981' : '#ef4444' }}">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            @if($positive)
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
            @else
            <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/>
            @endif
        </svg>
        {{ $trend }} vs. mês anterior
    </div>
    @elseif($sub)
    <div class="sa-tint-card__sub">{{ $sub }}</div>
    @endif
    @if($icon)
    <div class="sa-tint-card__icon">{!! $icon !!}</div>
    @endif
</div>
