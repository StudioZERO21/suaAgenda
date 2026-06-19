<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\Pagamento\GatewayFactory;
use App\Services\Pagamento\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web', 'company_id' => null]);

    $this->company = Company::create([
        'name' => 'Barbearia MP', 'slug' => 'barbearia-mp', 'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::create([
        'name' => 'Admin', 'email' => 'admin@mp.test',
        'password' => bcrypt('secret'), 'empresa_id' => $this->company->id, 'ativo' => true,
    ]);
    $this->user->assignRole('admin_empresa');
    $this->actingAs($this->user);

    config([
        'services.mercadopago.client_id' => 'test_client_id',
        'services.mercadopago.client_secret' => 'test_secret',
        'services.mercadopago.redirect_uri' => 'http://localhost/configuracoes/integracoes/mercadopago/callback',
        'services.mercadopago.pkce' => true,
    ]);
});

describe('mp_oauth_redirect', function () {
    it('redireciona para MP com state na sessão', function () {
        $response = $this->get('http://localhost/configuracoes/integracoes/mercadopago/conectar');

        $response->assertRedirectContains('auth.mercadopago.com');
        $response->assertRedirectContains('client_id=test_client_id');
        $response->assertRedirectContains('platform_id=mp');
        $response->assertRedirectContains('code_challenge=');
        expect(Session::get('mp_oauth_state'))->not->toBeNull();
        expect(Session::get('mp_oauth_pkce_verifier'))->not->toBeNull();
    });

    it('bloqueia redirect quando origem difere do callback OAuth', function () {
        $response = $this->get('http://suaagenda.local/configuracoes/integracoes/mercadopago/conectar');

        $response->assertRedirect(route('configuracoes', ['tab' => 'integracoes']));
        $response->assertSessionHas('error');
    });
});

describe('mp_oauth_callback', function () {
    it('rejeita state inválido e redireciona com erro', function () {
        Session::put('mp_oauth_state', 'state-real');

        $response = $this->get(route('mp.oauth.callback', [
            'code' => 'qualquer',
            'state' => 'state-errado',
        ]));

        $response->assertRedirect(route('configuracoes', ['tab' => 'integracoes']));
        $response->assertSessionHas('error');
    });

    it('trata param error do MP com redirecionamento para configurações', function () {
        $response = $this->get(route('mp.oauth.callback', [
            'error' => 'access_denied',
            'error_description' => 'Usuário negou acesso.',
        ]));

        $response->assertRedirect(route('configuracoes', ['tab' => 'integracoes']));
        $response->assertSessionHas('error');
    });

    it('salva tokens criptografados e ativa gateway após OAuth bem-sucedido', function () {
        $state = 'valid-state-abc123';
        Session::put('mp_oauth_state', $state);

        Http::fake([
            'api.mercadopago.com/oauth/token' => Http::response([
                'access_token' => 'APP_USR-test-access-token',
                'refresh_token' => 'TG-test-refresh-token',
                'user_id' => 99999,
            ], 200),
            'api.mercadopago.com/v1/users/me' => Http::response([
                'id' => 99999,
                'first_name' => 'Barbearia',
                'last_name' => 'Teste',
                'email' => 'barbearia@teste.com',
            ], 200),
        ]);

        $response = $this->get(route('mp.oauth.callback', [
            'code' => 'valid-code',
            'state' => $state,
        ]));

        $response->assertRedirect(route('configuracoes', ['tab' => 'integracoes']));
        $response->assertSessionHas('success');

        $this->company->refresh();
        $mp = $this->company->settings['integrations']['mercadopago'] ?? [];

        expect($mp['connected'])->toBeTrue();
        expect($mp['account_nome'])->toBe('Barbearia Teste');
        expect($mp['mp_user_id'])->toBe('99999');
        expect(decrypt($mp['access_token_enc']))->toBe('APP_USR-test-access-token');
        expect(decrypt($mp['refresh_token_enc']))->toBe('TG-test-refresh-token');
        expect($this->company->settings['integrations']['gateway'])->toBe('mercadopago');
    });
});

describe('mp_oauth_disconnect', function () {
    it('remove tokens e troca gateway para nenhum', function () {
        $settings = $this->company->settings ?? [];
        $settings['integrations']['gateway'] = 'mercadopago';
        $settings['integrations']['mercadopago'] = [
            'connected' => true,
            'access_token_enc' => encrypt('tok'),
            'account_nome' => 'Teste',
        ];
        $this->company->update(['settings' => $settings]);

        $response = $this->delete(route('mp.oauth.disconnect'));

        $response->assertRedirect(route('configuracoes', ['tab' => 'integracoes']));
        $response->assertSessionHas('success');

        $this->company->refresh();
        expect($this->company->settings['integrations']['mercadopago']['connected'])->toBeFalse();
        expect($this->company->settings['integrations']['gateway'])->toBe('nenhum');
    });
});

describe('mp_oauth_metrics', function () {
    it('retorna 422 quando MP não está conectado', function () {
        $this->getJson(route('mp.oauth.metrics'))->assertStatus(422)->assertJson(['ok' => false]);
    });

    it('retorna saldo e receita do mês quando conectado', function () {
        $settings = $this->company->settings ?? [];
        $settings['integrations']['mercadopago'] = [
            'connected' => true,
            'access_token_enc' => encrypt('APP_USR-tok'),
        ];
        $this->company->update(['settings' => $settings]);

        Http::fake([
            'api.mercadopago.com/v1/account/balance' => Http::response(['available_balance' => 1500.50], 200),
            'api.mercadopago.com/v1/payments/search*' => Http::response(['results' => [
                ['transaction_amount' => 300.00],
                ['transaction_amount' => 200.00],
            ]], 200),
        ]);

        $response = $this->getJson(route('mp.oauth.metrics'));

        $response->assertOk()->assertJson([
            'ok' => true,
            'balance' => 1500.50,
            'month_revenue' => 500.00,
        ]);
    });
});

describe('gateway_factory', function () {
    it('desencripta token OAuth ao testar MP', function () {
        Http::fake([
            'api.mercadopago.com/v1/payment_methods' => Http::response([], 200),
        ]);

        $result = GatewayFactory::testar([
            'gateway' => 'mercadopago',
            'mercadopago' => ['connected' => true, 'access_token_enc' => encrypt('APP_USR-tok')],
        ]);

        expect($result['ok'])->toBeTrue();
    });

    it('usa token legado plain text como fallback', function () {
        Http::fake([
            'api.mercadopago.com/v1/payment_methods' => Http::response([], 200),
        ]);

        $result = GatewayFactory::testar([
            'gateway' => 'mercadopago',
            'mercadopago' => ['access_token' => 'APP_USR-legado'],
        ]);

        expect($result['ok'])->toBeTrue();
    });
});

describe('mercadopago_service', function () {
    it('getAuthUrl inclui client_id, platform_id e state', function () {
        $url = MercadoPagoService::getAuthUrl('meu-state-123', 'challenge-abc');

        expect($url)->toContain('client_id=test_client_id');
        expect($url)->toContain('state=meu-state-123');
        expect($url)->toContain('platform_id=mp');
        expect($url)->toContain('code_challenge=challenge-abc');
        expect($url)->toContain('auth.mercadopago.com');
    });

    it('exchangeCode envia test_token em sandbox', function () {
        config(['services.mercadopago.ambiente' => 'sandbox']);

        Http::fake([
            'api.mercadopago.com/oauth/token' => Http::response(['access_token' => 'TEST-tok'], 200),
        ]);

        MercadoPagoService::exchangeCode('code-123', 'verifier-xyz');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.mercadopago.com/oauth/token'
                && $request['test_token'] === 'true'
                && $request['code_verifier'] === 'verifier-xyz';
        });
    });
});
