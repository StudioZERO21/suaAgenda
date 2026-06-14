<x-mail::message>
# Agendamento Cancelado

Olá, **{{ $agendamento->cliente?->name }}**!

Informamos que o agendamento abaixo foi cancelado.

<x-mail::panel>
**Serviço:** {{ $agendamento->servico?->nome ?? '—' }}
**Profissional:** {{ $agendamento->profissional?->name ?? '—' }}
**Data e Hora:** {{ $agendamento->data_hora->format('d/m/Y \à\s H:i') }}
@if($empresa)
**Empresa:** {{ $empresa->name }}
@endif
</x-mail::panel>

Para reagendar ou tirar dúvidas, entre em contato conosco.

Até logo,<br>
{{ $empresa?->name ?? config('app.name') }}
</x-mail::message>
