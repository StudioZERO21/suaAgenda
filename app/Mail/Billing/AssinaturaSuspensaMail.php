<?php

declare(strict_types=1);

namespace App\Mail\Billing;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssinaturaSuspensaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Company $company) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🚫 Conta suspensa — regularize sua assinatura suaAgenda.pro',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.billing.assinatura-suspensa',
            with: ['company' => $this->company],
        );
    }
}
