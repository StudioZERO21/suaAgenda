<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Alerta enviado quando o WhatsApp da empresa desconecta no Evolution.
 */
final class WhatsappDesconectado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Company $company) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'WhatsApp desconectado — reconecte no suaAgenda',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.whatsapp-desconectado',
            with: [
                'company' => $this->company,
                'urlConfig' => route('configuracoes.whatsapp'),
            ],
        );
    }
}
