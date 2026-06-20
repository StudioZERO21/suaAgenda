<x-mail::message>
# WhatsApp desconectado

Olá, **{{ $company->name }}**.

Detectamos que o WhatsApp da sua empresa foi **desconectado** do sistema. Enquanto isso não for corrigido:

- Notificações automáticas podem não ser enviadas pelo seu número
- Mensagens de clientes podem não chegar ao painel

## Como reconectar (leva menos de 1 minuto)

1. Acesse **Configurações → WhatsApp**
2. Clique em **Conectar**
3. Escaneie o **QR Code** com o celular da empresa

<x-mail::button :url="$urlConfig">
Reconectar WhatsApp
</x-mail::button>

Se precisar de ajuda, responda este e-mail.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
