<?php

declare(strict_types=1);

namespace App\Mail\Billing;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssinaturaCanceladaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Company $company) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Assinatura cancelada — suaAgenda.pro',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.billing.assinatura-cancelada',
            with: ['company' => $this->company],
        );
    }
}
