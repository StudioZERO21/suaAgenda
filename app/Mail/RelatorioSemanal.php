<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RelatorioSemanal extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Company $company,
        public readonly array $stats,
    ) {}

    public function envelope(): Envelope
    {
        $semana = now()->subDays(6)->format('d/m').' a '.now()->format('d/m/Y');

        return new Envelope(
            subject: "Relatório semanal — {$this->company->name} ({$semana})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.relatorio-semanal',
            with: [
                'company' => $this->company,
                'stats' => $this->stats,
            ],
        );
    }
}
