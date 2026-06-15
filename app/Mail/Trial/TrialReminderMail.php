<?php

declare(strict_types=1);

namespace App\Mail\Trial;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Company $company,
        /** Dia do lembrete: 4, 6 ou 7 */
        public readonly int $dia,
    ) {}

    public function envelope(): Envelope
    {
        $subjects = [
            4 => 'Seu trial expira em 3 dias — suaAgenda.pro',
            6 => 'Último aviso: 1 dia para o fim do trial',
            7 => 'Hoje é o último dia do seu trial!',
        ];

        return new Envelope(
            subject: $subjects[$this->dia] ?? 'Trial suaAgenda.pro',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.trial.reminder',
        );
    }
}
