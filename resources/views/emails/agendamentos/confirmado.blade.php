<x-mail::message>
# Agendamento Confirmado

Olá, **{{ $agendamento->cliente?->name }}**!

Seu agendamento foi confirmado com sucesso.

<x-mail::panel>
**Serviço:** {{ $agendamento->servico?->nome ?? '—' }}
**Profissional:** {{ $agendamento->profissional?->name ?? '—' }}
**Data e Hora:** {{ $agendamento->data_hora->format('d/m/Y \à\s H:i') }}
@if($empresa)
**Empresa:** {{ $empresa->name }}
@endif
</x-mail::panel>

Caso precise cancelar ou reagendar, entre em contato conosco.

Até logo,<br>
{{ $empresa?->name ?? config('app.name') }}
</x-mail::message>
