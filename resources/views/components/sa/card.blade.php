@props(['padding' => '24px', 'flush' => false])

{{--
    Atenção ao ';' final em "padding:{$padding};": o merge() do Laravel
    concatena o style padrão com o style passado pelo chamador. Sem o ';',
    o resultado vira "padding:20px margin-bottom:24px" (CSS inválido) e o
    navegador descarta o padding, deixando o conteúdo colado nas bordas.
--}}
<div {{ $attributes->merge([
    'class' => 'sa-card' . ($flush ? ' sa-card--flush' : ''),
    'style' => $flush ? null : "padding:{$padding};",
]) }}>
    {{ $slot }}
</div>
