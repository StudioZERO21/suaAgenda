<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Notificacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Notif', 'slug' => 'barbearia-notif',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makeNotifMR(string $companyId, bool $read = false): Notificacao
{
    return Notificacao::create([
        'company_id' => $companyId,
        'tipo' => 'novo_agendamento',
        'titulo' => 'Novo agendamento',
        'mensagem' => 'Teste',
        'read_at' => $read ? now() : null,
    ]);
}

describe('notificacao_mark_read', function () {
    it('markRead marca uma notificação como lida', function () {
        $notif = makeNotifMR($this->company->id);

        $this->actingAs($this->admin)
            ->patchJson(route('notificacoes.lida', $notif))
            ->assertOk()
            ->assertJson(['ok' => true]);

        expect($notif->fresh()->read_at)->not->toBeNull();
    });

    it('markAllRead marca todas as não lidas como lidas', function () {
        makeNotifMR($this->company->id);
        makeNotifMR($this->company->id);
        makeNotifMR($this->company->id, true);

        $this->actingAs($this->admin)
            ->patchJson(route('notificacoes.todas-lidas'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $unread = Notificacao::where('company_id', $this->company->id)->whereNull('read_at')->count();
        expect($unread)->toBe(0);
    });

    it('unreadCount retorna contagem de não lidas', function () {
        makeNotifMR($this->company->id);
        makeNotifMR($this->company->id);
        makeNotifMR($this->company->id, true);

        $data = $this->actingAs($this->admin)
            ->getJson(route('notificacoes.unread-count'))
            ->assertOk()
            ->json();

        expect($data['count'])->toBe(2);
    });

    it('markRead não permite marcar notificação de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-nf', 'plano' => 'trial', 'ativo' => true]);
        $notifOutra = makeNotifMR($outra->id);

        $this->actingAs($this->admin)
            ->patchJson(route('notificacoes.lida', $notifOutra))
            ->assertNotFound();
    });

    it('markAllRead não afeta notificações de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-nf2', 'plano' => 'trial', 'ativo' => true]);
        $notifOutra = makeNotifMR($outra->id);

        $this->actingAs($this->admin)
            ->patchJson(route('notificacoes.todas-lidas'))
            ->assertOk();

        expect($notifOutra->fresh()->read_at)->toBeNull();
    });

    it('analista pode marcar como lida', function () {
        $notif = makeNotifMR($this->company->id);

        $this->actingAs($this->analista)
            ->patchJson(route('notificacoes.lida', $notif))
            ->assertOk();
    });

    it('unauthenticated é rejeitado no markRead', function () {
        $notif = makeNotifMR($this->company->id);

        $this->patchJson(route('notificacoes.lida', $notif))
            ->assertUnauthorized();
    });

    it('unauthenticated é rejeitado no unreadCount', function () {
        $this->getJson(route('notificacoes.unread-count'))
            ->assertUnauthorized();
    });
});
