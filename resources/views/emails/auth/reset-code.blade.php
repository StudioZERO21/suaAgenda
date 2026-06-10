<x-mail::message>
# Recuperação de conta

Você solicitou a recuperação de acesso ao **suaAgenda.pro**.

Use o código abaixo para redefinir sua senha. Ele é válido por **15 minutos**.

<x-mail::panel>
<div style="text-align:center;font-size:36px;font-weight:800;letter-spacing:8px;color:#1a1a1a;font-family:monospace">{{ $code }}</div>
</x-mail::panel>

Se você não solicitou a recuperação de senha, ignore este e-mail com segurança.

Até logo,<br>
{{ config('app.name') }}
</x-mail::message>
