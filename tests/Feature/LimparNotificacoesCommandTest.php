<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Notificacao;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Barbearia Limpar', 'slug' => 'barbearia-limpar',
        'plano' => 'trial', 'ativo' => true,
    ]);
});

function makeLimparNotif(Company $company, bool $lida, int $diasAtras): Notificacao
{
    $notif = Notificacao::create([
        'company_id' => $company->id,
        'tipo' => 'novo_agendamento',
        'titulo' => 'Teste',
        'mensagem' => 'Msg',
    ]);

    $notif->created_at = now()->subDays($diasAtras);
    $notif->read_at = $lida ? now()->subDays($diasAtras) : null;
    $notif->saveQuietly();

    return $notif;
}

describe('limpar_notificacoes_command', function () {
    it('remove notificações lidas mais antigas que o limite padrão (90 dias)', function () {
        $antiga = makeLimparNotif($this->company, true, 100);
        $recente = makeLimparNotif($this->company, true, 10);

        $this->artisan('notificacoes:limpar')->assertSuccessful();

        expect(Notificacao::find($antiga->id))->toBeNull();
        expect(Notificacao::find($recente->id))->not->toBeNull();
    });

    it('não remove notificações não lidas mesmo antigas', function () {
        $naoLida = makeLimparNotif($this->company, false, 100);

        $this->artisan('notificacoes:limpar')->assertSuccessful();

        expect(Notificacao::find($naoLida->id))->not->toBeNull();
    });

    it('respeita --days personalizado', function () {
        $antiga30 = makeLimparNotif($this->company, true, 40);
        $nova10 = makeLimparNotif($this->company, true, 10);

        $this->artisan('notificacoes:limpar', ['--days' => 30])->assertSuccessful();

        expect(Notificacao::find($antiga30->id))->toBeNull();
        expect(Notificacao::find($nova10->id))->not->toBeNull();
    });

    it('limpa de múltiplas empresas ao mesmo tempo', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-limpar', 'plano' => 'trial', 'ativo' => true]);

        $n1 = makeLimparNotif($this->company, true, 100);
        $n2 = makeLimparNotif($outra, true, 100);

        $this->artisan('notificacoes:limpar')->assertSuccessful();

        expect(Notificacao::find($n1->id))->toBeNull();
        expect(Notificacao::find($n2->id))->toBeNull();
    });

    it('retorna success quando nada a limpar', function () {
        $this->artisan('notificacoes:limpar')->assertSuccessful();
    });

    it('output informa quantidade removida', function () {
        makeLimparNotif($this->company, true, 100);
        makeLimparNotif($this->company, true, 100);

        $this->artisan('notificacoes:limpar')
            ->assertSuccessful()
            ->expectsOutputToContain('2');
    });
});
