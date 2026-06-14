<x-mail::message>
# ⚠️ Fatura Vencida

Olá, **{{ $company->name }}**!

Identificamos uma fatura em atraso na sua conta suaAgenda.pro. Regularize para evitar a suspensão do serviço.

<x-mail::panel>
**Fatura:** {{ $invoice->number }}
**Valor:** R$ {{ number_format((float) $invoice->amount, 2, ',', '.') }}
**Vencimento:** {{ $invoice->due_date->format('d/m/Y') }}
**Dias em atraso:** {{ $invoice->due_date->diffInDays(now()) }} dias
</x-mail::panel>

@if($invoice->gateway_payment_url)
<x-mail::button :url="$invoice->gateway_payment_url" color="error">
Pagar agora e evitar suspensão
</x-mail::button>
@endif

**Prazo para regularização:** sua conta será suspensa caso o pagamento não seja realizado em breve. Após a suspensão, você terá mais 30 dias para regularizar antes do cancelamento definitivo.

Em caso de dúvidas, entre em contato com nosso suporte.

Equipe suaAgenda.pro
</x-mail::message>
