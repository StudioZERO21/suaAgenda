<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Limites mensais de notificações por plano
    | -1 = ilimitado
    |--------------------------------------------------------------------------
    */
    'planos' => [
        'trial' => ['whatsapp' => 50, 'sms' => 20, 'email' => 100],
        'starter' => ['whatsapp' => 200, 'sms' => 100, 'email' => 500],
        'crescimento' => ['whatsapp' => 1000, 'sms' => 500, 'email' => 2000],
        'profissional' => ['whatsapp' => 5000, 'sms' => 2000, 'email' => 10000],
        'enterprise' => ['whatsapp' => -1, 'sms' => -1, 'email' => -1],
        'default' => ['whatsapp' => 100, 'sms' => 50, 'email' => 200],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custo estimado por mensagem (USD) — usado no dashboard de gastos Twilio
    |--------------------------------------------------------------------------
    */
    'custo_por_mensagem' => [
        'twilio_whatsapp' => 0.005,
        'twilio_sms' => 0.0079,
    ],

    /*
    |--------------------------------------------------------------------------
    | Percentual de alerta (notifica quando uso >= X% do limite)
    |--------------------------------------------------------------------------
    */
    'alerta_percentual' => 80,
];
