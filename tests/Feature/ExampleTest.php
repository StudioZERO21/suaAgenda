<?php

test('rota raiz redireciona para login', function () {
    $r = $this->get('/');
    $r->assertRedirect('/login');
});
test('login exibe formulario', function () {
    $r = $this->get('/login');
    $r->assertStatus(200);
});
