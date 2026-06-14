<x-mail::message>
# Pagamento Confirmado ✓

Olá, **{{ $company->name }}**!

Seu pagamento foi recebido com sucesso. Sua assinatura está ativa e em dia.

<x-mail::panel>
**Fatura:** {{ $invoice->number }}
**Valor pago:** R$ {{ number_format((float) $invoice->amount, 2, ',', '.') }}
**Pago em:** {{ $invoice->paid_at?->format('d/m/Y') }}
</x-mail::panel>

Obrigado por manter sua assinatura em dia! Continue gerenciando seus agendamentos normalmente.

Equipe suaAgenda.pro
</x-mail::message>
