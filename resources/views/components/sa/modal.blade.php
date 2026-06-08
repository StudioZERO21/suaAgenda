@props(['title' => null, 'subtitle' => null, 'size' => 'md', 'open' => 'open'])

@php
    $maxW = match ($size) {
        'sm' => '460px',
        'lg' => '820px',
        default => '600px',
    };
@endphp

<div x-show="{{ $open }}" x-cloak
     @keydown.escape.window="{{ $open }} = false"
     style="position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:1000;padding:20px"
     @click.self="{{ $open }} = false">
    <div style="background:var(--sa-surface);border-radius:16px;width:100%;max-width:{{ $maxW }};max-height:90vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.2);animation:sa-modal-in 250ms ease">
        <div style="padding:24px 28px 0;display:flex;justify-content:space-between;align-items:flex-start;flex-shrink:0">
            <div>
                @if($title)
                <h3 style="font-family:var(--sa-font-heading);font-size:18px;font-weight:600;color:var(--sa-text1);margin:0">{!! $title !!}</h3>
                @endif
                @if($subtitle)
                <p style="font-size:13px;color:var(--sa-text3);margin:4px 0 0">{!! $subtitle !!}</p>
                @endif
            </div>
            <button type="button" @click="{{ $open }} = false" style="background:none;border:none;cursor:pointer;color:var(--sa-text3);padding:4px;display:flex;border-radius:6px">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div style="padding:20px 28px;overflow-y:auto;flex:1">
            {{ $slot }}
        </div>
        @isset($footer)
        <div style="padding:16px 28px 24px;border-top:1px solid var(--sa-border);display:flex;gap:10px;justify-content:flex-end;flex-shrink:0">
            {{ $footer }}
        </div>
        @endisset
    </div>
</div>
