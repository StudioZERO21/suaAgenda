<?php

declare(strict_types=1);

namespace App\Mail\Billing;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FaturaGeradaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Invoice $invoice) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Fatura {$this->invoice->number} — vencimento em {$this->invoice->due_date->format('d/m/Y')}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.billing.fatura-gerada',
            with: ['invoice' => $this->invoice, 'company' => $this->invoice->company],
        );
    }
}
