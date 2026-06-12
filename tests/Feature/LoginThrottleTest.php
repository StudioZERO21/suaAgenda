<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('teste@exemplo.com|127.0.0.1');

    $this->user = User::create([
        'name' => 'Teste',
        'email' => 'teste@exemplo.com',
        'password' => Hash::make('SenhaCorreta123!'),
    ]);
});

describe('login_throttle', function () {
    it('bloqueia após 5 tentativas falhas com mensagem de muitas tentativas', function () {
        foreach (range(1, 5) as $i) {
            $this->post('/login', [
                'email' => 'teste@exemplo.com',
                'password' => 'senha-errada',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->post('/login', [
            'email' => 'teste@exemplo.com',
            'password' => 'senha-errada',
        ]);

        $response->assertSessionHasErrors('email');
        $erros = session('errors')->get('email');
        expect($erros[0])->toContain('Muitas tentativas');
    });

    it('login com sucesso limpa o contador de tentativas', function () {
        foreach (range(1, 4) as $i) {
            $this->post('/login', [
                'email' => 'teste@exemplo.com',
                'password' => 'senha-errada',
            ]);
        }

        $this->post('/login', [
            'email' => 'teste@exemplo.com',
            'password' => 'SenhaCorreta123!',
        ])->assertRedirect(route('dashboard'));

        $this->post('/logout');

        // contador zerado: nova tentativa falha não bloqueia
        $this->post('/login', [
            'email' => 'teste@exemplo.com',
            'password' => 'senha-errada',
        ])->assertSessionHasErrors('email');

        $erros = session('errors')->get('email');
        expect($erros[0])->not->toContain('Muitas tentativas');
    });

    it('rota de login retorna 429 após estourar o throttle de requisições', function () {
        foreach (range(1, 10) as $i) {
            $this->post('/login', [
                'email' => "outro{$i}@exemplo.com",
                'password' => 'senha-errada',
            ]);
        }

        $this->post('/login', [
            'email' => 'outro@exemplo.com',
            'password' => 'senha-errada',
        ])->assertStatus(429);
    });
});
