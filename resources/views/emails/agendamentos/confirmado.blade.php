<x-mail::message>
# Agendamento Confirmado

Olá, **{{ $agendamento->cliente?->name }}**!

Seu agendamento foi recebido com sucesso.

<x-mail::panel>
**Serviço:** {{ $agendamento->servico?->nome ?? '—' }}
**Profissional:** {{ $agendamento->profissional?->name ?? '—' }}
**Data e Hora:** {{ $agendamento->data_hora->format('d/m/Y \à\s H:i') }}
**Duração:** {{ $agendamento->duracao }} minutos
@if($agendamento->valor)
**Valor:** R$ {{ number_format((float) $agendamento->valor, 2, ',', '.') }}
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

Você pode acompanhar o status, visualizar detalhes ou cancelar o agendamento pelo link acima.

Até logo,<br>
{{ $empresa?->name ?? config('app.name') }}
</x-mail::message>
