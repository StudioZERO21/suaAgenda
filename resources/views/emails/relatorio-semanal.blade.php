<x-mail::message>
# Relatório Semanal — {{ $company->name }}

Olá! Aqui está o resumo da última semana ({{ now()->subDays(6)->format('d/m') }} a {{ now()->format('d/m/Y') }}).

<x-mail::panel>
**Agendamentos realizados:** {{ $stats['total'] }}
**Finalizados:** {{ $stats['finalizados'] }}
**Receita bruta:** R$ {{ number_format($stats['receita'], 2, ',', '.') }}
**Ticket médio:** R$ {{ number_format($stats['ticket_medio'], 2, ',', '.') }}
@if($stats['top_servico'])
**Serviço mais popular:** {{ $stats['top_servico'] }}
@endif
@if($stats['top_profissional'])
**Profissional destaque:** {{ $stats['top_profissional'] }}
@endif
</x-mail::panel>

Acesse o painel para ver o relatório completo.

Abraços,<br>
{{ config('app.name') }}
</x-mail::message>
