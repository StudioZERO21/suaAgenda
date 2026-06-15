@component('mail::message')
# Olá, {{ $company->name }}!

@if($dia === 7)
**Hoje é o último dia do seu trial** na suaAgenda.pro.

Após hoje, o acesso ao painel será suspenso até que você escolha um plano.
@elseif($dia === 6)
**Seu trial expira amanhã!**

Você tem apenas **1 dia** para garantir o acesso contínuo ao suaAgenda.pro.
@else
**Seu trial expira em {{ 8 - $dia }} dias.**

Aproveite os dias restantes e garanta que seus agendamentos continuem funcionando!
@endif

---

**O que acontece se eu não assinar?**

Seu acesso ao painel será suspenso automaticamente. Seus dados ficam preservados por 30 dias, mas novos agendamentos e notificações param de funcionar.

@component('mail::button', ['url' => route('planos.index'), 'color' => 'primary'])
Ver Planos e Assinar
@endcomponent

Tem dúvidas? Responda este e-mail — estamos aqui para ajudar.

Obrigado por testar o **suaAgenda.pro**!

{{ config('app.name') }}
@endcomponent
