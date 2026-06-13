<x-mail::message>
# Olá, {{ $cliente->name }} 👋

Use o botão abaixo para acessar sua área em **{{ $company->name }}**. O link é válido por 15 minutos e pode ser usado apenas uma vez.

<x-mail::button :url="$url">
Acessar minha área
</x-mail::button>

Se você não solicitou este acesso, pode ignorar este e-mail com segurança.

Obrigado,<br>
{{ $company->name }}
</x-mail::message>
