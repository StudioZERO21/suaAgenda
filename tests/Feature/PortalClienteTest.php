<?php

declare(strict_types=1);

use App\Mail\ClienteMagicLink;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ClienteLoginToken;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create(['name' => 'Barbearia Portal', 'slug' => 'barbearia-portal', 'plano' => 'trial', 'ativo' => true]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'Maria Cliente', 'phone' => '11999990000',
        'email' => 'maria@portal.test',
    ]);
});

describe('portal_cliente', function () {
    it('envia link mágico por e-mail e responde de forma neutra', function () {
        Mail::fake();

        $this->post(route('portal.enviar-link', $this->company->slug), [
            'contato' => 'maria@portal.test', 'canal' => 'email',
        ])->assertRedirect()->assertSessionHas('enviado');

        Mail::assertQueued(ClienteMagicLink::class);
        expect(ClienteLoginToken::count())->toBe(1);
    });

    it('não revela se o contato existe (anti-enumeração)', function () {
        Mail::fake();

        $this->post(route('portal.enviar-link', $this->company->slug), [
            'contato' => 'naoexiste@x.test', 'canal' => 'email',
        ])->assertRedirect()->assertSessionHas('enviado');

        Mail::assertNothingQueued();
        expect(ClienteLoginToken::count())->toBe(0);
    });

    it('autentica o cliente via token válido', function () {
        ['token' => $token] = ClienteLoginToken::gerar($this->cliente, 'email', '127.0.0.1');

        $this->get(route('portal.entrar.token', ['slug' => $this->company->slug, 'token' => $token]))
            ->assertRedirect(route('portal.dashboard', $this->company->slug));

        expect(auth('cliente')->id())->toBe($this->cliente->id);
    });

    it('rejeita token expirado', function () {
        ['token' => $token, 'model' => $registro] = ClienteLoginToken::gerar($this->cliente, 'email', '127.0.0.1');
        $registro->update(['expires_at' => now()->subMinute()]);

        $this->get(route('portal.entrar.token', ['slug' => $this->company->slug, 'token' => $token]))
            ->assertRedirect(route('portal.entrar', $this->company->slug))
            ->assertSessionHas('erro');

        expect(auth('cliente')->check())->toBeFalse();
    });

    it('token é de uso único', function () {
        ['token' => $token] = ClienteLoginToken::gerar($this->cliente, 'email', '127.0.0.1');

        $this->get(route('portal.entrar.token', ['slug' => $this->company->slug, 'token' => $token]))->assertRedirect();
        auth('cliente')->logout();

        $this->get(route('portal.entrar.token', ['slug' => $this->company->slug, 'token' => $token]))
            ->assertRedirect(route('portal.entrar', $this->company->slug))
            ->assertSessionHas('erro');
    });

    it('cliente da empresa A não acessa portal da empresa B', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-portal', 'plano' => 'trial', 'ativo' => true]);

        $this->actingAs($this->cliente, 'cliente')
            ->get(route('portal.dashboard', $outra->slug))
            ->assertRedirect(route('portal.entrar', $outra->slug));
    });

    it('dashboard mostra histórico e total gasto do cliente', function () {
        $servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'cor' => '#111', 'ativo' => true]);
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);

        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $prof->id, 'servico_id' => $servico->id,
            'data_hora' => now()->subDays(2), 'duracao' => 30, 'valor' => 80,
            'status' => Agendamento::STATUS_FINALIZADO,
        ]);
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $prof->id, 'servico_id' => $servico->id,
            'data_hora' => now()->addDay(), 'duracao' => 30, 'valor' => 50,
            'status' => Agendamento::STATUS_CONFIRMADO,
        ]);

        $resposta = $this->actingAs($this->cliente, 'cliente')
            ->get(route('portal.dashboard', $this->company->slug))
            ->assertOk()
            ->assertViewIs('portal.dashboard');

        expect($resposta->viewData('totalGasto'))->toBe(80.0)
            ->and($resposta->viewData('totalAtendimentos'))->toBe(1)
            ->and($resposta->viewData('proximos'))->toHaveCount(1);
    });

    it('cliente exporta os próprios dados', function () {
        $this->actingAs($this->cliente, 'cliente')
            ->get(route('portal.dados.exportar', $this->company->slug))
            ->assertOk()
            ->assertJsonPath('dados_pessoais.nome', 'Maria Cliente');
    });

    it('cliente não autenticado é redirecionado ao login', function () {
        $this->get(route('portal.dashboard', $this->company->slug))
            ->assertRedirect(route('portal.entrar', $this->company->slug));
    });
});
