<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Agendamento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgendamentoConfirmado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Agendamento $agendamento) {}

    public function envelope(): Envelope
    {
        $data = $this->agendamento->data_hora->format('d/m/Y \à\s H:i');

        return new Envelope(
            subject: "Agendamento confirmado — {$data}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.agendamentos.confirmado',
            with: [
                'agendamento' => $this->agendamento,
                'empresa' => $this->agendamento->company,
            ],
        );
    }
}
