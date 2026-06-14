<x-mail::message>
# Nova Fatura Disponível

Olá, **{{ $company->name }}**!

Uma nova fatura foi gerada para sua assinatura suaAgenda.pro.

<x-mail::panel>
**Fatura:** {{ $invoice->number }}
**Valor:** R$ {{ number_format((float) $invoice->amount, 2, ',', '.') }}
**Vencimento:** {{ $invoice->due_date->format('d/m/Y') }}
</x-mail::panel>

@if($invoice->gateway_payment_url)
<x-mail::button :url="$invoice->gateway_payment_url" color="success">
Pagar via PIX agora
</x-mail::button>
@endif

O pagamento via PIX é processado automaticamente. Após a confirmação, sua conta será atualizada em até 5 minutos.

Equipe suaAgenda.pro
</x-mail::message>
