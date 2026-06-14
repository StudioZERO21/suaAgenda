<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Paletas de cores do design system suaAgenda.pro.
 */
final class SaPalettes
{
    /** @var array<string, string> */
    public const DESCRIPTIONS = [
        'A' => 'Barbearia premium',
        'B' => 'Corporativo & sóbrio',
        'C' => 'Moderno & tech',
        'D' => 'Natural & wellness',
        'E' => 'Beauty & feminino',
        'F' => 'Areia & terracota',
        'G' => 'Branco & índigo',
        'H' => 'Creme & âmbar',
        'I' => 'Verde Harmony',
        'J' => 'Preto & laranja',
        'K' => 'Preto & roxo',
        'L' => 'Branco & roxo',
    ];

    /**
     * Retorna todas as paletas para a tela de configurações.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return array_values(self::definitions());
    }

    /**
     * Retorna uma paleta pelo identificador.
     *
     * @return array<string, mixed>|null
     */
    public static function get(string $id): ?array
    {
        return self::definitions()[$id] ?? null;
    }

    /**
     * Gera bloco CSS com variáveis para modo claro e escuro.
     */
    public static function cssBlock(string $id): string
    {
        $palette = self::get($id) ?? self::get('A');

        return ':root{'.self::cssVariablesFromColors($palette['light'], false).'}'
            .'html.sa-dark{'.self::cssVariablesFromColors($palette['dark'], true).'}';
    }

    /**
     * URL do Google Fonts conforme fontes escolhidas.
     */
    public static function googleFontsUrl(string $heading, string $body): string
    {
        $families = [
            'poppins' => 'Poppins:wght@400;500;600;700;800',
            'montserrat' => 'Montserrat:wght@400;500;600;700;800',
            'jakarta' => 'Plus+Jakarta+Sans:wght@400;500;600;700;800',
            'dm-serif' => 'DM+Serif+Display:ital@0;1',
            'inter' => 'Inter:wght@400;500;600;700',
            'dm-sans' => 'DM+Sans:wght@400;500;600;700',
            'nunito' => 'Nunito:wght@400;500;600;700',
            'lato' => 'Lato:wght@400;700',
        ];

        $selected = array_unique([$heading, $body]);
        $query = collect($selected)
            ->map(fn (string $key) => 'family='.($families[$key] ?? $families['inter']))
            ->implode('&');

        return "https://fonts.googleapis.com/css2?{$query}&display=swap";
    }

    /**
     * Resolve fontes para injeção no layout (CSS + Google Fonts).
     *
     * @return array{heading_key: string, body_key: string, heading_css: string, body_css: string, google_url: string}
     */
    public static function resolveFonts(string $heading, string $body): array
    {
        $names = self::fontFamilyNames($heading, $body);

        return [
            'heading_key' => $heading,
            'body_key' => $body,
            'heading_css' => $names['heading'],
            'body_css' => $body === 'inter'
                ? "'Inter', -apple-system, sans-serif"
                : $names['body'],
            'google_url' => self::googleFontsUrl($heading, $body),
        ];
    }

    /**
     * Mapa de chaves → font-family CSS (pré-visualização no front).
     *
     * @return array<string, string>
     */
    public static function fontCssMap(): array
    {
        return [
            'poppins' => "'Poppins', sans-serif",
            'montserrat' => "'Montserrat', sans-serif",
            'jakarta' => "'Plus Jakarta Sans', sans-serif",
            'dm-serif' => "'DM Serif Display', serif",
            'inter' => "'Inter', -apple-system, sans-serif",
            'dm-sans' => "'DM Sans', sans-serif",
            'nunito' => "'Nunito', sans-serif",
            'lato' => "'Lato', sans-serif",
        ];
    }

    /**
     * Nomes CSS das famílias tipográficas.
     *
     * @return array{heading: string, body: string}
     */
    public static function fontFamilyNames(string $heading, string $body): array
    {
        $map = [
            'poppins' => "'Poppins', sans-serif",
            'montserrat' => "'Montserrat', sans-serif",
            'jakarta' => "'Plus Jakarta Sans', sans-serif",
            'dm-serif' => "'DM Serif Display', serif",
            'inter' => "'Inter', sans-serif",
            'dm-sans' => "'DM Sans', sans-serif",
            'nunito' => "'Nunito', sans-serif",
            'lato' => "'Lato', sans-serif",
        ];

        return [
            'heading' => $map[$heading] ?? $map['poppins'],
            'body' => $map[$body] ?? $map['inter'],
        ];
    }

    /**
     * Gera variáveis CSS para aplicar a paleta no layout.
     */
    public static function cssVariables(string $id, bool $dark = false): string
    {
        $palette = self::get($id) ?? self::get('A');
        $colors = $palette[$dark ? 'dark' : 'light'];

        return self::cssVariablesFromColors($colors, $dark);
    }

    /**
     * @param  array<string, string>  $colors
     */
    private static function cssVariablesFromColors(array $colors, bool $dark): string
    {
        $vars = [
            '--sa-primary' => $colors['primary'],
            '--sa-primary-l' => $colors['primaryLight'],
            '--sa-secondary' => $colors['secondary'],
            '--sa-secondary-l' => $colors['secondaryLight'],
            '--sa-bg' => $colors['bg'],
            '--sa-surface' => $colors['surface'],
            '--sa-surface2' => $colors['surface2'],
            '--sa-text1' => $colors['text1'],
            '--sa-text2' => $colors['text2'],
            '--sa-text3' => $colors['text3'],
            '--sa-border' => $colors['border'],
            '--sa-border2' => $colors['border2'],
            '--sa-side-accent' => $colors['secondary'],
        ];

        if ($dark) {
            $vars['--sa-side-bg'] = $colors['surface'];
            $vars['--sa-side-text'] = $colors['text1'];
            $vars['--sa-side-muted'] = $colors['text3'];
        } else {
            $vars['--sa-side-bg'] = '#111111';
            $vars['--sa-side-text'] = '#eeeeee';
            $vars['--sa-side-muted'] = '#888888';
        }

        return collect($vars)
            ->map(fn (string $value, string $key) => "{$key}:{$value}")
            ->implode(';');
    }

    /**
     * Configurações padrão da empresa (JSON settings).
     *
     * @return array<string, mixed>
     */
    public static function defaultCompanySettings(): array
    {
        return [
            'theme_palette' => 'A',
            'dark_mode' => false,
            'heading_font' => 'poppins',
            'body_font' => 'inter',
            'notifications' => [
                'channel' => 'whatsapp',
                'new_booking' => true,
                'cancelled' => true,
                'reminder' => true,
                'no_show' => false,
                'daily_summary' => true,
                'weekly_report' => false,
            ],
            'security' => [
                'twofa' => false,
                'logins_email' => true,
                'session_timeout' => 30,
                'api_access' => true,
            ],
            'contacts' => [
                'support' => '',
                'billing' => '',
                'instagram' => '',
                'facebook' => '',
                'youtube' => '',
            ],
            'hours' => [
                'seg' => ['08:00', '20:00', true],
                'ter' => ['08:00', '20:00', true],
                'qua' => ['08:00', '20:00', true],
                'qui' => ['08:00', '20:00', true],
                'sex' => ['08:00', '20:00', true],
                'sab' => ['08:00', '16:00', true],
                'dom' => ['09:00', '14:00', false],
            ],
            'closure' => [
                'active' => false,
                'status' => 'ferias',
                'return_date' => null,
                'note' => '',
            ],
            'advanced' => [
                'confirm_required' => false,
                'auto_reminder' => true,
                'reminder_hours' => 24,
                'cancel_policy' => '',
                'min_advance_mins' => 30,
                'max_advance_days' => 60,
            ],
            'payments' => [
                'pix_key' => '',
                'pix_key_type' => 'random',
                'pix_city' => '',
            ],
            'integrations' => [
                'whatsapp' => [
                    'ativo' => false,
                    'twilio_sid' => '',
                    'twilio_token' => '',
                    'twilio_numero' => '',
                ],
                'gateway' => 'nenhum',
                'mercadopago' => ['access_token' => ''],
                'asaas' => ['api_key' => '', 'ambiente' => 'sandbox'],
                'stripe' => ['publishable_key' => '', 'secret_key' => ''],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function definitions(): array
    {
        return [
            'A' => self::palette('A', 'Cinza + Dourado', ['#1a1a1a', '#d4a574'],
                ['#1a1a1a', '#2d2d2d', '#d4a574', '#e6c299', '#f5f5f5', '#ffffff', '#fafafa', '#1a1a1a', '#5a5a5a', '#999999', '#e2e2e2', '#d0d0d0'],
                ['#d4a574', '#e6c299', '#d4a574', '#e6c299', '#0f0f0f', '#1a1a1a', '#242424', '#f0f0f0', '#b0b0b0', '#707070', '#2d2d2d', '#3a3a3a']),
            'B' => self::palette('B', 'Preto + Petróleo', ['#000000', '#1b4965'],
                ['#000000', '#1e1e1e', '#1b4965', '#2d7098', '#f8f9fa', '#ffffff', '#f0f4f8', '#000000', '#404040', '#757575', '#d0d5dd', '#bcc4cc'],
                ['#4a90e2', '#6aaaf5', '#2d7098', '#4a90e2', '#0a0e1a', '#141c2a', '#1e2d40', '#f0f2f5', '#a0aec0', '#637085', '#1e2d40', '#2a3d55']),
            'C' => self::palette('C', 'Preto + Azul Tech', ['#000000', '#0066ff'],
                ['#000000', '#1a1a1a', '#0066ff', '#3b82f6', '#fafbfc', '#ffffff', '#f0f5ff', '#000000', '#374151', '#6b7280', '#e5e7eb', '#d1d5db'],
                ['#3b82f6', '#60a5fa', '#0066ff', '#3b82f6', '#080f1c', '#111827', '#1e2d42', '#f0f4ff', '#94a3b8', '#64748b', '#1e2d42', '#2d3f58']),
            'D' => self::palette('D', 'Preto + Verde', ['#000000', '#10b981'],
                ['#000000', '#1a1a1a', '#10b981', '#34d399', '#f0fdf8', '#ffffff', '#ecfdf5', '#000000', '#374151', '#6b7280', '#d1fae5', '#a7f3d0'],
                ['#10b981', '#34d399', '#10b981', '#34d399', '#071912', '#0d2218', '#14322a', '#ecfdf5', '#6ee7b7', '#34d399', '#14322a', '#1f4a3a']),
            'E' => self::palette('E', 'Rosa + Branco', ['#ec4899', '#f9a8d4'],
                ['#ec4899', '#f472b6', '#db2777', '#ec4899', '#fff1f8', '#ffffff', '#fdf2f8', '#18060e', '#7c2d52', '#b06075', '#fce7f3', '#fbcfe8'],
                ['#f472b6', '#f9a8d4', '#ec4899', '#f472b6', '#180810', '#2a1022', '#3a1830', '#ffe8f5', '#d4a0b8', '#a06080', '#3a1830', '#4a2040']),
            'F' => self::palette('F', 'Areia + Terracota', ['#c2714f', '#e8d5b0'],
                ['#c2714f', '#d4845f', '#a85c3a', '#c2714f', '#faf5ec', '#fffdf8', '#f5ede0', '#2c1a0e', '#6b4c35', '#a08060', '#e8d8c0', '#d8c4a8'],
                ['#e09070', '#f0a880', '#c2714f', '#d4845f', '#1a0f08', '#2a1a10', '#3a2418', '#fdf0e0', '#c8a888', '#8a6848', '#3a2418', '#4a3428']),
            'G' => self::palette('G', 'Branco + Índigo', ['#4338ca', '#a5b4fc'],
                ['#4338ca', '#4f46e5', '#6366f1', '#818cf8', '#f8f8ff', '#ffffff', '#f0f0fe', '#0f0f23', '#3d3d5c', '#7070a0', '#e0e0f8', '#d0d0f0'],
                ['#818cf8', '#a5b4fc', '#6366f1', '#818cf8', '#08080f', '#12121f', '#1e1e32', '#f0f0ff', '#a0a0c8', '#606088', '#1e1e38', '#2a2a48']),
            'H' => self::palette('H', 'Creme + Âmbar', ['#92400e', '#fbbf24'],
                ['#92400e', '#a85010', '#d97706', '#f59e0b', '#fffbf0', '#ffffff', '#fef9e7', '#1c0f04', '#5c3a14', '#9a6830', '#f0e0b0', '#e8d098'],
                ['#fbbf24', '#fcd34d', '#d97706', '#f59e0b', '#120b00', '#1e1200', '#2c1a00', '#fff8e8', '#d4a860', '#907040', '#2c1a00', '#3c2800']),
            'I' => self::palette('I', 'Verde Harmony', ['#1e3d2b', '#6aaa7a'],
                ['#1e3d2b', '#2a5239', '#6aaa7a', '#8cc49a', '#eef1e8', '#f8faf5', '#e4eadc', '#0f1e14', '#2d4a35', '#6a8a72', '#ccdec8', '#b8d0b2'],
                ['#6aaa7a', '#8cc49a', '#4a8a5a', '#6aaa7a', '#080f0a', '#101a12', '#18261b', '#e8f4ea', '#9abea0', '#5a7a60', '#18261b', '#22352a']),
            'J' => self::palette('J', 'Preto + Laranja', ['#111111', '#f97316'],
                ['#111111', '#222222', '#f97316', '#fb923c', '#fafaf9', '#ffffff', '#f7f4ef', '#0a0a0a', '#3a3a3a', '#808080', '#e4e0d8', '#d0ccc4'],
                ['#f97316', '#fb923c', '#ea6c0a', '#f97316', '#0a0600', '#140c00', '#1e1400', '#fff4ec', '#c8a080', '#806040', '#1e1400', '#2a1c00']),
            'K' => self::palette('K', 'Preto + Roxo', ['#0a0a0a', '#7c3aed'],
                ['#0a0a0a', '#1e1e1e', '#7c3aed', '#9b5bf5', '#faf9ff', '#ffffff', '#f3f0ff', '#0a0a0a', '#3a3545', '#7a7090', '#ddd8f0', '#ccc4e8'],
                ['#9b5bf5', '#b47dff', '#7c3aed', '#9b5bf5', '#06040f', '#0e0a1a', '#160f26', '#f0ecff', '#a898cc', '#6858a0', '#160f26', '#201535']),
            'L' => self::palette('L', 'Branco + Roxo', ['#7c3aed', '#c4b5fd'],
                ['#7c3aed', '#8b4cf5', '#5b21b6', '#7c3aed', '#fdfcff', '#ffffff', '#f5f2ff', '#1a0a3d', '#3d2a6a', '#7a6a9a', '#e4dcf8', '#d4c8f4'],
                ['#c4b5fd', '#ddd6fe', '#9b5bf5', '#c4b5fd', '#06040f', '#0e0a1a', '#160f26', '#f0ecff', '#b8a8e0', '#7868a8', '#160f26', '#201535']),
        ];
    }

    /**
     * @param  array<int, string>  $swatches
     * @param  array<int, string>  $light
     * @param  array<int, string>  $dark
     * @return array<string, mixed>
     */
    private static function palette(
        string $id,
        string $name,
        array $swatches,
        array $light,
        array $dark,
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'description' => self::DESCRIPTIONS[$id] ?? '',
            'swatches' => $swatches,
            'light' => [
                'primary' => $light[0],
                'primaryLight' => $light[1],
                'secondary' => $light[2],
                'secondaryLight' => $light[3],
                'bg' => $light[4],
                'surface' => $light[5],
                'surface2' => $light[6],
                'text1' => $light[7],
                'text2' => $light[8],
                'text3' => $light[9],
                'border' => $light[10],
                'border2' => $light[11],
            ],
            'dark' => [
                'primary' => $dark[0],
                'primaryLight' => $dark[1],
                'secondary' => $dark[2],
                'secondaryLight' => $dark[3],
                'bg' => $dark[4],
                'surface' => $dark[5],
                'surface2' => $dark[6],
                'text1' => $dark[7],
                'text2' => $dark[8],
                'text3' => $dark[9],
                'border' => $dark[10],
                'border2' => $dark[11],
            ],
        ];
    }
}
