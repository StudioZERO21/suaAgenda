<x-mail::message>
# Lembrete de Agendamento

Olá, {{ $agendamento->cliente?->name ?? 'Cliente' }}!

Este é um lembrete de que você tem um agendamento **amanhã**.

<x-mail::panel>
**Data:** {{ $agendamento->data_hora->format('d/m/Y') }}
**Horário:** {{ $agendamento->data_hora->format('H:i') }}
**Serviço:** {{ $agendamento->servico?->nome ?? '—' }}
**Profissional:** {{ $agendamento->profissional?->name ?? '—' }}
</x-mail::panel>

Caso precise cancelar, entre em contato com antecedência.

Abraços,<br>
{{ config('app.name') }}
</x-mail::message>
