<x-mail::message>
# Lembrete de Agendamento

Olá, **{{ $agendamento->cliente?->name }}**!

Este é um lembrete de que você tem um agendamento **amanhã**.

<x-mail::panel>
**Serviço:** {{ $agendamento->servico?->nome ?? '—' }}
**Profissional:** {{ $agendamento->profissional?->name ?? '—' }}
**Data e Hora:** {{ $agendamento->data_hora->format('d/m/Y \à\s H:i') }}
@if($empresa)
**Local:** {{ $empresa->name }}
@endif
</x-mail::panel>

Se precisar cancelar ou reagendar, entre em contato o quanto antes.

Até amanhã,<br>
{{ $empresa?->name ?? config('app.name') }}
</x-mail::message>
