<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Ícones pictográficos por segmento profissional.
 *
 * Prioridade: SVG em public/assets/icons/servicos/{key}.svg (estilo Flaticon, preto).
 * Fallback: traços inline em PATHS para ícones sem arquivo.
 */
final class SaServiceIcons
{
    public const DEFAULT = 'servico_generico';

    private const ASSET_DIR = 'assets/icons/servicos';

    /** @var array<string, string> */
    private const ICON_SEGMENT_PRIORITY = [
        'scissors' => 'barbearia',
        'razor' => 'barbearia',
        'navalha' => 'barbearia',
        'barba' => 'barbearia',
        'bigode' => 'barbearia',
        'star' => 'barbearia',
        'user' => 'barbearia',
        'comb' => 'cabeleireiro',
        'brush' => 'cabeleireiro',
        'wind' => 'cabeleireiro',
        'droplet' => 'cabeleireiro',
        'palette' => 'cabeleireiro',
        'waves' => 'cabeleireiro',
        'sparkle' => 'cabeleireiro',
        'flower' => 'cabeleireiro',
        'eye' => 'maquiagem',
        'pen_tool' => 'maquiagem',
        'heart' => 'pet',
        'activity' => 'saude',
    ];

    /** @var array<string, string> */
    private const PATHS = [
        // Fallback mínimo
        'servico_generico' => '<path d="M16.5 9.4 7.55 4.24"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/>',
        'tag' => '<path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/>',
        'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',

        // ── BARBEARIA (barba e cabelo masculino) ─────────────────────────────
        'barber_pole' => '<rect x="10" y="3" width="4" height="18" rx="1"/><path d="M10 5l4 4-4 4"/><path d="M14 9l-4 4 4 4"/><path d="M10 13l4 4-4 4"/><line x1="10" y1="2" x2="14" y2="2"/><line x1="10" y1="22" x2="14" y2="22"/>',
        'navalha' => '<path d="M5 5l12 12"/><path d="M5 5 9 3l7 7-2 4z"/><path d="M17 17l2 2"/>',
        'razor' => '<path d="M6 4c4 0 8 2 10 6l-2 8H8L6 4z"/><line x1="8" y1="12" x2="16" y2="12"/><path d="M6 4 4 2"/>',
        'barba_silhueta' => '<path d="M8 7c0-2 1.8-3.5 4-3.5s4 1.5 4 3.5"/><path d="M6 9c0 5.5 2.7 9 6 9s6-3.5 6-9"/><path d="M9 15c0 .8.9 1.5 3 1.5s3-.7 3-1.5"/>',
        'barba' => '<path d="M5 12c0 3.5 3 6 7 6s7-2.5 7-6"/><path d="M8 10V8a4 4 0 0 1 8 0v2"/><path d="M9 14h.01M15 14h.01"/>',
        'bigode' => '<path d="M7 13c0 1.2 2.2 2 5 2s5-.8 5-2"/><path d="M6 12h12"/>',
        'barba_tesoura' => '<path d="M8 9c0-1.5 1.5-2.5 4-2.5s4 1 4 2.5"/><path d="M7 11c0 4 2.5 7 5 7s5-3 5-7"/><circle cx="5" cy="5" r="2"/><circle cx="5" cy="11" r="2"/><line x1="18" y1="3" x2="7" y2="10"/>',
        'rosto_barba' => '<circle cx="12" cy="8" r="4"/><path d="M8 12c0 4 1.8 6 4 6s4-2 4-6"/><path d="M9 15.5c0 .8 1 1.5 3 1.5s3-.7 3-1.5"/>',
        'scissors' => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/>',
        'tesoura_pente' => '<circle cx="5" cy="5" r="2"/><circle cx="5" cy="13" r="2"/><line x1="16" y1="3" x2="7" y2="12"/><path d="M14 3v18"/><path d="M17 3v18"/><path d="M20 3v18"/>',
        'maquina_corte' => '<rect x="6" y="5" width="12" height="11" rx="2"/><circle cx="9" cy="9" r="1" fill="currentColor" stroke="none"/><line x1="12" y1="8" x2="16" y2="8"/><line x1="12" y1="11" x2="16" y2="11"/><path d="M12 16v4"/>',
        'comb' => '<path d="M4 4h16v2H4z"/><path d="M6 6v14"/><path d="M10 6v14"/><path d="M14 6v14"/><path d="M18 6v14"/>',
        'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',

        // ── CABELEIREIRO (cabelo feminino / salão) ───────────────────────────
        'mulher_corte' => '<circle cx="11" cy="7" r="4"/><path d="M7 11c0 4 1.5 7 4 7s4-3 4-7"/><circle cx="18" cy="6" r="2"/><circle cx="18" cy="12" r="2"/><line x1="8" y1="4" x2="16" y2="10"/>',
        'mulher_perfil' => '<path d="M15 4c2.5 0 4.5 2 4.5 4.5V18c0 1.5-1 2.5-2.5 2.5"/><path d="M15 4C12 4 9 6.5 9 10v8c0 2.5 1 4 3 4"/><path d="M10 8C7 9 6 12 6 15"/><path d="M19 6l1 1"/><path d="M20 9h1"/>',
        'mulher_brilho' => '<circle cx="11" cy="8" r="4"/><path d="M7 12c0 3.5 1.5 6 4 6s4-2.5 4-6"/><path d="M17 4l1 1-1 1"/><path d="M19 7h2"/><path d="M17 9l1 1"/>',
        'mulher_coloracao' => '<circle cx="9" cy="8" r="3.5"/><path d="M6 11c0 3 1 5 3 5s3-2 3-5"/><rect x="15" y="6" width="4" height="9" rx="1"/><path d="M16 4h2v2"/><path d="M19 8l1 1"/>',
        'mulher_progressiva' => '<path d="M7 5v14"/><path d="M11 4v16"/><path d="M15 6v12"/><path d="M19 4l1 1-1 1"/><path d="M20 8h2"/>',
        'mulher_penteado' => '<circle cx="12" cy="7" r="3.5"/><path d="M8 10c0 0 1-3 2-3 1 0 1.5 2 2 2s1-2 2-2 1 3 2 3"/><path d="M7 14c0 0 1-2 2-2 1 0 1.5 2 3 2s2-2 3-2 2 2 2 2"/><path d="M18 5l1 1"/><path d="M19 8h1"/>',
        'mulher_cacheado' => '<circle cx="12" cy="7" r="3"/><path d="M7 11c0 0 1-2 2-2 1 0 1.5 2 3 2s2-2 3-2 1 2 2 2"/><path d="M7 15c0 0 1-2 2-2 1 0 1.5 2 3 2s2-2 3-2 1 2 2 2"/>',
        'mulher_mascara' => '<circle cx="10" cy="8" r="3.5"/><path d="M6.5 8h7l-.8 5.5c0 1.8-1 3-2.7 3s-2.7-1.2-2.7-3L6.5 8z"/><rect x="16" y="11" width="4" height="5" rx="1"/><path d="M17 9h2"/><path d="M19 7l1 1"/>',
        'shampoo_salao' => '<rect x="9" y="8" width="6" height="11" rx="1"/><path d="M10 8V6a2 2 0 0 1 4 0v2"/><path d="M11 4h2v2"/><path d="M10 12h6"/>',
        'secador' => '<path d="M8 10h9a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2H8z"/><path d="M6 10V8a2 2 0 0 1 2-2h2"/><path d="M17 7c2 0 3 1 3 2s-1 2-3 2"/><path d="M12 15v3"/>',
        'escova_capilar' => '<path d="M8 20V8l8-4v16"/><path d="M8 12h8"/><path d="M8 16h8"/>',
        'waves' => '<path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/>',
        'droplet' => '<path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>',
        'sparkle' => '<path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5z"/>',
        'flower' => '<path d="M12 7.5a4.5 4.5 0 1 1 4.5 4.5M12 7.5A4.5 4.5 0 1 0 7.5 12M12 7.5V9m-4.5 3a4.5 4.5 0 1 1 4.5 4.5M12 16.5a4.5 4.5 0 1 1-4.5-4.5m9 0a4.5 4.5 0 1 1-4.5 4.5"/><circle cx="12" cy="12" r="3"/>',
        'palette' => '<circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>',
        'brush' => '<path d="m9.06 11.9 8.07-8.06a2.85 2.85 0 1 1 4.03 4.03l-8.06 8.08"/><path d="M7.07 14.94c-1.66 0-3 1.35-3 3.02 0 1.33-2.5 1.52-2 2.02 1.08 1.1 2.49 2.02 4 2.02 2.2 0 4-1.8 4-4.04a3.01 3.01 0 0 0-3-3.02z"/>',
        'wind' => '<path d="M17.7 7.7a2.5 2.5 0 1 1 1.8 4.3H2"/><path d="M9.6 4.6A2 2 0 1 1 11 8H2"/><path d="M12.6 19.4A2 2 0 1 0 14 16H2"/>',

        // Estética & Spa
        'scan_face' => '<path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><circle cx="12" cy="10" r="3"/><path d="M8 16c.5-1.5 2-2.5 4-2.5s3.5 1 4 2.5"/>',
        'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
        'gem' => '<path d="M6 3h12l4 6-10 13L2 9Z"/><path d="M11 3 8 9l4 13 4-13-3-6"/><path d="M2 9h20"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/>',
        'moon' => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
        'smile' => '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/>',

        // Manicure
        'hand' => '<path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0"/><path d="M14 10V4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v2"/><path d="M10 10.5V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v8"/><path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.7-2.3L6 18"/>',
        'fingerprint' => '<path d="M12 10a2 2 0 0 0-2 2c0 1.02-.1 2.51-.26 4"/><path d="M14 13.12c0 2.38 0 6.38-1 8.88"/><path d="M17.29 21.02c.12-.6.43-2.3.5-3.02"/><path d="M2 12a10 10 0 0 1 18-6"/>',

        // Maquiagem
        'pincel_make' => '<path d="M12 2 9 9h6L12 2z"/><path d="M8 10h8l-1 11H9L8 10z"/><line x1="10" y1="14" x2="14" y2="14"/>',
        'pen_tool' => '<path d="m12 19 7-7 3 3-7 7-3-3z"/><path d="m18 13-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/>',
        'eye' => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',

        // Pet
        'paw' => '<circle cx="11" cy="4" r="2"/><circle cx="18" cy="8" r="2"/><circle cx="20" cy="16" r="2"/><circle cx="4" cy="16" r="2"/><circle cx="6" cy="8" r="2"/><path d="M12 22c4.97 0 9-2.582 9-6.5S17 9 12 9s-9 2.082-9 6.5S7.03 22 12 22z"/>',
        'dog' => '<path d="M10 5.172C10 3.782 8.423 2.679 6.5 3c-2.823.47-4.113 6.006-4 7 .137 1.217 1.088 2.28 2.5 2.5 1.088.2 2.088-.5 2.5-1.5"/><path d="M14 5.172C14 3.782 15.577 2.679 17.5 3c2.823.47 4.113 6.006 4 7-.137 1.217-1.088 2.28-2.5 2.5-1.088.2-2.088-.5-2.5-1.5"/><path d="M8 14v.5"/><path d="M16 14v.5"/><path d="M11.25 16.25h1.5L12 17l-.75-.75Z"/>',
        'cat' => '<path d="M12 5c.67 0 1.35.09 2 .26 1.78-2 5.03-2.84 6.42-2.26 1.4.58-.42 7-.42 7 .57 1.07 1 2.24 1 3.44C21 17.9 16.97 21 12 21s-9-3-9-7.56c0-1.25.5-2.4 1-3.44 0 0-1.82-6.42-.42-7 1.39-.58 4.64.26 6.42 2.26.65-.17 1.33-.26 2-.26z"/>',
        'bone' => '<path d="M17 10c.7-.7 1.69 0 2.5 0a3.5 3.5 0 1 0 0-7c-.81 0-1.8.7-2.5 0a3.5 3.5 0 1 0-6.6 2.3c-.7.7-1.69 0-2.5 0a3.5 3.5 0 1 0 0 7c.81 0 1.8-.7 2.5 0a3.5 3.5 0 1 0 6.6-2.3z"/>',
        'heart' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
        'fish' => '<path d="M6.5 12c.94-3.46 4.94-6 8.5-6 3.56 0 6.06 2.54 7 6-.94 3.46-3.44 6-7 6-3.56 0-7.56-2.54-8.5-6Z"/><path d="M18 12v.5"/><circle cx="10.5" cy="12" r=".5" fill="currentColor"/>',
        'bird' => '<path d="M16 7h.01"/><path d="M3.4 18H12a8 8 0 0 0 8-8 7.8 7.8 0 0 0-3-6.2"/><path d="m2 17 10-10"/>',
        'spray' => '<path d="M3 3h.01"/><path d="M7 5h.01"/><rect width="4" height="4" x="15" y="5" rx="1"/><path d="M19 9v10a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V9"/>',

        // Saúde
        'stethoscope' => '<path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"/><path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"/><circle cx="20" cy="10" r="2"/>',
        'activity' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
        'pill' => '<path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/>',
        'syringe' => '<path d="m18 2 4 4"/><path d="M19 9 8.7 19.3c-1 1-2.5 1-3.4 0l-.6-.6c-1-1-1-2.5 0-3.4L15 5"/><path d="m9 11 4 4"/>',
        'brain' => '<path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96.44"/><path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96.44"/>',
        'bandage' => '<path d="m14 12-8.5 8.5a2.12 2.12 0 1 0 3 3L17 15"/><path d="m17 11 4.4-4.4a2.12 2.12 0 1 0-3-3L14 8"/>',
        'clipboard' => '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>',

        // Fitness
        'dumbbell' => '<path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m14 14 7-7"/>',
        'bike' => '<circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/>',
        'footprints' => '<path d="M4 16v-2.38C4 11.5 2.97 10.5 3 8c.03-2.72 1.49-3 2-3 1.52 0 1.83 1.92 2 3 .17 1.13.83 2 2 2 1.67 0 2.5-1.33 2.5-2.5S10.67 5 9 5"/>',
        'trophy' => '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>',
        'timer' => '<line x1="10" x2="14" y1="2" y2="2"/><circle cx="12" cy="14" r="8"/><line x1="12" y1="14" x2="15" y2="11"/>',

        // Empresa
        'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>',
        'building' => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4"/>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>',
        'presentation' => '<path d="M2 3h20"/><path d="M21 3v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V3"/><path d="m7 21 5-5 5 5"/>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/>',
        'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'file_text' => '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><line x1="16" y1="13" x2="8" y2="13"/>',
        'calendar' => '<rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'laptop' => '<path d="M20 16V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9"/><path d="M2 18h20"/>',

        // Alimentação
        'utensils' => '<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>',
        'coffee' => '<path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/>',
        'chef_hat' => '<path d="M17 21a1 1 0 0 0 1-1v-5H6v5a1 1 0 0 0 1 1h10Z"/><path d="M6 3v7a6 6 0 0 0 12 0V3"/>',
        'wine' => '<path d="M8 22h8"/><path d="M7 10h10"/><path d="M12 15v7"/><path d="M12 15a5 5 0 0 0 5-5c0-2-.5-4-2-8H9c-1.5 4-2 6-2 8a5 5 0 0 0 5 5Z"/>',
        'cake' => '<path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8"/><path d="M2 21h20"/>',
        'beef' => '<circle cx="12.5" cy="8.5" r="2.5"/><path d="M12.5 2v6"/><path d="M12.5 13v9"/>',

        // Automotivo
        'car' => '<path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>',
        'wrench' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'fuel' => '<line x1="3" x2="15" y1="22" y2="22"/><path d="M14 22V4a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v18"/>',
        'gauge' => '<path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/>',
        'car_front' => '<path d="m16 6-6-3-6 3"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>',

        // Casa
        'home' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'sofa' => '<path d="M20 9V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v3"/><path d="M2 11v5a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-5"/>',
        'hammer' => '<path d="m15 12-8.5 8.5c-.83.83-2.17.83-3 0a2.12 2.12 0 0 1 0-3L12 9"/><path d="M17.64 15 22 10.64"/>',
        'truck' => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',

        // Educação
        'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
        'camera' => '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>',
        'graduation_cap' => '<path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>',
        'music' => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/>',
        'video' => '<path d="m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5"/><rect x="2" y="6" width="14" height="12" rx="2"/>',
        'mic' => '<path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="22"/>',
    ];

    /** @var array<string, string> */
    private const LABELS = [
        'servico_generico' => 'Serviço genérico',
        'tag' => 'Etiqueta',
        'star' => 'Combo barba + corte',
        'clock' => 'Horário',

        // Barbearia
        'barber_pole' => 'Poste de barbearia',
        'navalha' => 'Navalha aberta',
        'razor' => 'Navalha / barbear',
        'barba_silhueta' => 'Barba',
        'barba' => 'Barba completa',
        'bigode' => 'Bigode',
        'barba_tesoura' => 'Barba com tesoura',
        'rosto_barba' => 'Rosto com barba',
        'scissors' => 'Tesoura / corte',
        'tesoura_pente' => 'Tesoura e pente',
        'maquina_corte' => 'Máquina de corte',
        'comb' => 'Pente',
        'user' => 'Cliente masculino',

        // Cabeleireiro
        'mulher_corte' => 'Corte feminino',
        'mulher_perfil' => 'Cabelo longo / perfil',
        'mulher_brilho' => 'Brilho / finalização',
        'mulher_coloracao' => 'Coloração / tintura',
        'mulher_progressiva' => 'Progressiva / alisamento',
        'mulher_penteado' => 'Penteado / escova',
        'mulher_cacheado' => 'Cabelo cacheado',
        'mulher_mascara' => 'Máscara capilar / spa',
        'shampoo_salao' => 'Shampoo / produto',
        'secador' => 'Secador',
        'escova_capilar' => 'Escova',
        'waves' => 'Ondulação',
        'droplet' => 'Hidratação',
        'sparkle' => 'Tratamento brilho',
        'flower' => 'Penteado de noiva',
        'palette' => 'Coloração',
        'brush' => 'Escova modeladora',
        'wind' => 'Secagem',

        // Estética
        'scan_face' => 'Limpeza de pele',
        'leaf' => 'Massagem / spa',
        'gem' => 'Tratamento premium',
        'sun' => 'Bronzeamento',
        'moon' => 'Spa relax',
        'smile' => 'Estética facial',

        // Manicure / Make
        'hand' => 'Manicure',
        'fingerprint' => 'Nail art',
        'pincel_make' => 'Pincel de make',
        'pen_tool' => 'Contorno / design',
        'eye' => 'Maquiagem olhos',

        // Pet
        'paw' => 'Pet (geral)',
        'dog' => 'Cachorro',
        'cat' => 'Gato',
        'bone' => 'Pet shop',
        'heart' => 'Banho pet',
        'fish' => 'Aquário',
        'bird' => 'Aves',
        'spray' => 'Banho / tosa',

        // Saúde
        'stethoscope' => 'Consulta médica',
        'activity' => 'Exame clínico',
        'pill' => 'Medicamento',
        'syringe' => 'Vacina / injeção',
        'brain' => 'Neurologia / psique',
        'bandage' => 'Curativo',
        'clipboard' => 'Prontuário',

        // Fitness
        'dumbbell' => 'Musculação',
        'bike' => 'Ciclismo',
        'footprints' => 'Corrida',
        'trophy' => 'Competição',
        'timer' => 'Treino funcional',

        // Empresa
        'briefcase' => 'Consultoria',
        'building' => 'Empresa',
        'users' => 'Equipe',
        'presentation' => 'Treinamento',
        'phone' => 'Telefone',
        'mail' => 'E-mail',
        'file_text' => 'Documento',
        'calendar' => 'Reunião',
        'laptop' => 'Online',

        // Alimentação
        'utensils' => 'Restaurante',
        'coffee' => 'Café',
        'chef_hat' => 'Chef / cozinha',
        'wine' => 'Bar',
        'cake' => 'Confeitaria',
        'beef' => 'Churrascaria',

        // Automotivo
        'car' => 'Mecânica',
        'wrench' => 'Oficina',
        'fuel' => 'Combustível',
        'gauge' => 'Diagnóstico',
        'car_front' => 'Estética auto',

        // Casa
        'home' => 'Residencial',
        'sofa' => 'Limpeza',
        'hammer' => 'Reparos',
        'truck' => 'Entrega',

        // Educação
        'book' => 'Curso / aula',
        'camera' => 'Foto',
        'graduation_cap' => 'Formação',
        'music' => 'Música',
        'video' => 'Vídeo aula',
        'mic' => 'Podcast',
    ];

    /** @return array<string, array{label: string, highlight: bool, description: string, icons: list<string>}> */
    public static function categories(): array
    {
        return [
            'barbearia' => [
                'label' => 'Barbearia',
                'highlight' => true,
                'description' => 'Barba, bigode, navalha, máquina e corte masculino.',
                'icons' => [
                    'barber_pole', 'navalha', 'razor', 'barba_silhueta', 'barba', 'bigode',
                    'barba_tesoura', 'rosto_barba', 'scissors', 'tesoura_pente', 'maquina_corte', 'comb',
                ],
            ],
            'cabeleireiro' => [
                'label' => 'Cabeleireiro / Salão',
                'highlight' => true,
                'description' => 'Corte, coloração, escova, hidratação e tratamentos capilares femininos.',
                'icons' => [
                    'mulher_corte', 'mulher_perfil', 'mulher_brilho', 'mulher_coloracao',
                    'mulher_progressiva', 'mulher_penteado', 'mulher_cacheado', 'mulher_mascara',
                    'shampoo_salao', 'secador', 'escova_capilar', 'palette',
                ],
            ],
            'estetica' => [
                'label' => 'Estética & Spa',
                'highlight' => true,
                'description' => 'Facial, corporal, depilação, massagem e relaxamento.',
                'icons' => ['scan_face', 'smile', 'leaf', 'flower', 'gem', 'sun', 'moon', 'droplet', 'sparkle'],
            ],
            'manicure' => [
                'label' => 'Manicure & Pedicure',
                'highlight' => true,
                'description' => 'Unhas, esmaltação e nail art.',
                'icons' => ['hand', 'fingerprint', 'sparkle', 'droplet', 'gem'],
            ],
            'maquiagem' => [
                'label' => 'Maquiagem',
                'highlight' => true,
                'description' => 'Make social, festa, noiva e produção artística.',
                'icons' => ['pincel_make', 'palette', 'pen_tool', 'eye', 'scan_face', 'sparkle', 'flower'],
            ],
            'pet' => [
                'label' => 'Pet Shop & Veterinário',
                'highlight' => true,
                'description' => 'Banho, tosa, consulta e cuidados com animais.',
                'icons' => ['paw', 'dog', 'cat', 'bone', 'heart', 'fish', 'bird', 'spray'],
            ],
            'saude' => [
                'label' => 'Saúde & Clínica',
                'highlight' => true,
                'description' => 'Consultas, exames, vacinas e procedimentos clínicos.',
                'icons' => ['stethoscope', 'activity', 'pill', 'syringe', 'brain', 'eye', 'bandage', 'clipboard'],
            ],
            'fitness' => [
                'label' => 'Fitness & Esporte',
                'highlight' => true,
                'description' => 'Academia, personal trainer e modalidades esportivas.',
                'icons' => ['dumbbell', 'bike', 'footprints', 'trophy', 'timer', 'activity'],
            ],
            'empresa' => [
                'label' => 'Empresa & Consultoria',
                'highlight' => false,
                'description' => 'Reuniões, treinamentos e serviços corporativos.',
                'icons' => ['briefcase', 'building', 'users', 'presentation', 'phone', 'mail', 'file_text', 'calendar', 'laptop'],
            ],
            'alimentacao' => [
                'label' => 'Alimentação & Gastronomia',
                'highlight' => false,
                'description' => 'Restaurantes, bares, cafés e confeitaria.',
                'icons' => ['utensils', 'coffee', 'chef_hat', 'wine', 'cake', 'beef'],
            ],
            'automotivo' => [
                'label' => 'Automotivo & Oficina',
                'highlight' => false,
                'description' => 'Mecânica, diagnóstico e estética automotiva.',
                'icons' => ['car', 'wrench', 'fuel', 'gauge', 'car_front'],
            ],
            'casa' => [
                'label' => 'Casa & Limpeza',
                'highlight' => false,
                'description' => 'Serviços domésticos, limpeza e manutenção residencial.',
                'icons' => ['home', 'spray', 'sofa', 'hammer', 'truck'],
            ],
            'educacao' => [
                'label' => 'Educação & Criativo',
                'highlight' => false,
                'description' => 'Aulas, cursos, foto, vídeo e produção de conteúdo.',
                'icons' => ['book', 'camera', 'graduation_cap', 'music', 'video', 'mic'],
            ],
            'generico' => [
                'label' => 'Genérico',
                'highlight' => false,
                'description' => 'Somente quando nenhum segmento se aplica.',
                'icons' => ['servico_generico', 'tag', 'clock'],
            ],
        ];
    }

    public static function categoryForIcon(?string $key): string
    {
        $key = self::normalize($key);

        if (isset(self::ICON_SEGMENT_PRIORITY[$key])) {
            return self::ICON_SEGMENT_PRIORITY[$key];
        }

        foreach (self::categories() as $id => $cat) {
            if (in_array($key, $cat['icons'], true)) {
                return $id;
            }
        }

        return 'generico';
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::PATHS);
    }

    public static function isValid(?string $key): bool
    {
        return $key !== null && isset(self::PATHS[$key]);
    }

    public static function normalize(?string $key): string
    {
        if ($key === null || $key === '') {
            return self::DEFAULT;
        }

        return self::isValid($key) ? $key : self::DEFAULT;
    }

    public static function path(string $key): string
    {
        return self::PATHS[self::normalize($key)];
    }

    public static function hasAsset(string $key): bool
    {
        return is_file(self::assetFilesystemPath($key));
    }

    public static function assetUrl(string $key): ?string
    {
        $key = self::normalize($key);

        if (! self::hasAsset($key)) {
            return null;
        }

        return asset(self::ASSET_DIR.'/'.$key.'.svg');
    }

    /** @return array<string, string> */
    public static function urls(): array
    {
        $urls = [];

        foreach (self::keys() as $key) {
            $url = self::assetUrl($key);
            if ($url !== null) {
                $urls[$key] = $url;
            }
        }

        return $urls;
    }

    private static function assetFilesystemPath(string $key): string
    {
        return public_path(self::ASSET_DIR.'/'.self::normalize($key).'.svg');
    }

    /** @return array<string, string> */
    public static function paths(): array
    {
        return self::PATHS;
    }

    public static function label(string $key): string
    {
        return self::LABELS[self::normalize($key)] ?? ucfirst(str_replace('_', ' ', $key));
    }

    /** @return list<array{id: string, label: string, highlight: bool, description: string, icons: list<array{key: string, label: string}>}> */
    public static function catalogForJs(): array
    {
        return array_values(array_map(
            fn (string $id, array $cat) => [
                'id' => $id,
                'label' => $cat['label'],
                'highlight' => $cat['highlight'],
                'description' => $cat['description'],
                'icons' => array_map(
                    fn (string $key) => ['key' => $key, 'label' => self::label($key)],
                    $cat['icons'],
                ),
            ],
            array_keys(self::categories()),
            self::categories(),
        ));
    }
}
