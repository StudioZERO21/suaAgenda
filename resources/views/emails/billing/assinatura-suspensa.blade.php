<x-mail::message>
# 🚫 Conta Suspensa por Inadimplência

Olá, **{{ $company->name }}**!

Sua conta suaAgenda.pro foi **suspensa temporariamente** devido a faturas em aberto não pagas.

**O que isso significa:**
- O acesso ao painel está bloqueado
- Os agendamentos públicos estão desativados
- Seus dados estão preservados e seguros

Para reativar sua conta, regularize o pagamento pendente. Após a confirmação do pagamento, o acesso será restaurado automaticamente em até 5 minutos.

<x-mail::button :url="config('app.url').'/login'" color="error">
Regularizar agora
</x-mail::button>

**Atenção:** se o pagamento não for regularizado em 30 dias a partir da suspensão, a conta será cancelada permanentemente.

Equipe suaAgenda.pro
</x-mail::message>
