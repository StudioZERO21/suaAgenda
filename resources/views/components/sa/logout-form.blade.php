@props(['variant' => 'sidebar', 'class' => ''])

<form method="POST" action="{{ route('logout') }}" {{ $attributes->merge(['class' => $class]) }}>
    @csrf

    @if($variant === 'sidebar')
    <button type="button" onclick="saConfirmLogout(this)" class="sa-side-btn sa-side-btn--logout" style="width:100%;margin-top:6px">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--sa-side-muted);flex-shrink:0">
            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        <span>Sair</span>
    </button>
    @elseif($variant === 'icon')
    <button type="button" onclick="saConfirmLogout(this)" title="Sair" style="background:none;border:none;cursor:pointer;color:var(--sa-side-muted);padding:8px;border-radius:8px;transition:background 150ms"
            onmouseover="this.style.background='rgba(255,255,255,.08)'" onmouseout="this.style.background='none'">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
    </button>
    @else
    <x-sa.btn type="button" variant="danger" size="sm" onclick="saConfirmLogout(this)">
        Sair da Conta
    </x-sa.btn>
    @endif
</form>
