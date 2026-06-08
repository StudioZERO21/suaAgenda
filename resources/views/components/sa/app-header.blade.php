@props(['title', 'subtitle' => null])

<div class="sa-app-header">
    <div>
        <h1 class="sa-app-header__title">{{ $title }}</h1>
        @if($subtitle)
        <p class="sa-app-header__subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
    <div class="sa-app-header__actions">{{ $actions }}</div>
    @endisset
</div>
