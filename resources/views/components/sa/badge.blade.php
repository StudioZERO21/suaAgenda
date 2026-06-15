@props(['status', 'label' => null])

@php
    $cfg = match($status) {
        'confirmado', 'confirmed', 'active', 'ativo' => ['bg' => 'rgba(16,185,129,.12)', 'color' => '#059669', 'label' => 'Confirmado'],
        'pendente', 'pending', 'ferias', 'vacation' => ['bg' => 'rgba(245,158,11,.12)', 'color' => '#d97706', 'label' => 'Pendente'],
        'licenca' => ['bg' => 'rgba(99,102,241,.12)', 'color' => '#6366f1', 'label' => 'Licença'],
        'aguardando_sinal' => ['bg' => 'rgba(245,158,11,.12)', 'color' => '#d97706', 'label' => 'Ag. Sinal'],
        'cancelado', 'cancelled' => ['bg' => 'rgba(239,68,68,.1)', 'color' => '#dc2626', 'label' => 'Cancelado'],
        'finalizado' => ['bg' => 'rgba(107,114,128,.12)', 'color' => '#6b7280', 'label' => 'Finalizado'],
        'inactive', 'inativo' => ['bg' => 'rgba(107,114,128,.12)', 'color' => '#6b7280', 'label' => 'Inativo'],
        default => ['bg' => 'rgba(0,0,0,.06)', 'color' => 'var(--sa-text2)', 'label' => ucfirst($status)],
    };
    $text = $label ?? $cfg['label'];
@endphp

<span class="sa-badge" style="background:{{ $cfg['bg'] }};color:{{ $cfg['color'] }}">
    <span class="sa-badge__dot"></span>
    {{ $text }}
</span>
