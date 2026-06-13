<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Cliente;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClienteMagicLink extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Cliente $cliente,
        public readonly Company $company,
        public readonly string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Seu acesso — '.$this->company->name);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.portal.magic-link');
    }
}
