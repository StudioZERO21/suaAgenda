<x-mail::message>
# Pagamento Confirmado ✓

Olá, **{{ $agendamento->cliente?->name }}**!

Seu pagamento foi recebido com sucesso. Obrigado!

<x-mail::panel>
**Serviço:** {{ $agendamento->servico?->nome ?? '—' }}
**Data e Hora:** {{ $agendamento->data_hora->format('d/m/Y \à\s H:i') }}
@if($agendamento->valor)
**Valor pago:** R$ {{ number_format((float) $agendamento->valor, 2, ',', '.') }}
@endif
@if($empresa)
**Empresa:** {{ $empresa->name }}
@endif
</x-mail::panel>

@if($agendamento->cancel_token)
<x-mail::button :url="route('agendamento.meu', $agendamento->cancel_token)">
Ver meu agendamento
</x-mail::button>
@endif

Até logo,<br>
{{ $empresa?->name ?? config('app.name') }}
</x-mail::message>
