<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'starter',
                'nome' => 'Starter',
                'preco' => 49.90,
                'max_profissionais' => 1,
                'whatsapp_mensal' => 50,
                'sms_mensal' => 50,
                'max_whatsapp_overage' => 300,
                'color' => '#6b7280',
                'popular' => false,
                'ordem' => 1,
                'features' => [
                    'Calendário completo',
                    'Agendamentos ilimitados',
                    'Link personalizado',
                    '1 relatório (receita)',
                    'Notificações automáticas',
                    'LGPD compliance',
                ],
            ],
            [
                'slug' => 'crescimento',
                'nome' => 'Crescimento',
                'preco' => 99.90,
                'max_profissionais' => 4,
                'whatsapp_mensal' => 200,
                'sms_mensal' => 200,
                'max_whatsapp_overage' => 800,
                'color' => '#6366f1',
                'popular' => true,
                'ordem' => 2,
                'features' => [
                    'Tudo do Starter',
                    '2–4 profissionais',
                    '3 relatórios completos',
                    'Marketing: Aniversariantes',
                    'App mobile completo',
                    'Google Calendar sync',
                    'Customização: Cores + Logo',
                ],
            ],
            [
                'slug' => 'profissional',
                'nome' => 'Profissional',
                'preco' => 199.90,
                'max_profissionais' => 15,
                'whatsapp_mensal' => 500,
                'sms_mensal' => 500,
                'max_whatsapp_overage' => 2000,
                'color' => '#d4a574',
                'popular' => false,
                'ordem' => 3,
                'features' => [
                    'Tudo do Crescimento',
                    'Todos os 6 relatórios',
                    'IA: Análise de padrões',
                    'Clientes em risco',
                    'Comissões automáticas',
                    'MercadoPago integrado',
                ],
            ],
            [
                'slug' => 'enterprise',
                'nome' => 'Enterprise',
                'preco' => 399.90,
                'max_profissionais' => -1,
                'whatsapp_mensal' => -1,
                'sms_mensal' => -1,
                'max_whatsapp_overage' => -1,
                'color' => '#1a1a1a',
                'popular' => false,
                'ordem' => 4,
                'features' => [
                    'Tudo do Profissional',
                    'Multi-unidade',
                    'Domínio customizado',
                    'IA Chatbot',
                    'API REST',
                    'Suporte 24/7',
                    'Consultoria (25h/ano)',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
