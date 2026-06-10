<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Agendamento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LembreteAgendamento extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Agendamento $agendamento,
    ) {}

    public function envelope(): Envelope
    {
        $data = $this->agendamento->data_hora->format('d/m/Y');
        $hora = $this->agendamento->data_hora->format('H:i');

        return new Envelope(
            subject: "Lembrete: seu agendamento é amanhã ({$data} às {$hora})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.lembrete-agendamento',
            with: ['agendamento' => $this->agendamento],
        );
    }
}
