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

    $this->company = Company::create([
        'name' => 'Barbearia Notif', 'slug' => 'barbearia-notif',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');
});

function makeNotif($company, string $tipo = 'novo_agendamento'): Notificacao
{
    return Notificacao::create([
        'company_id' => $company->id,
        'tipo' => $tipo,
        'titulo' => 'Teste',
        'mensagem' => 'Mensagem de teste',
    ]);
}

describe('notificacao_destroy', function () {
    it('pode deletar notificação da própria empresa', function () {
        $notif = makeNotif($this->company);

        $this->actingAs($this->user)
            ->deleteJson(route('notificacoes.destroy', $notif))
            ->assertOk()
            ->assertJson(['ok' => true]);

        expect(Notificacao::find($notif->id))->toBeNull();
    });

    it('não pode deletar notificação de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-notif', 'plano' => 'trial', 'ativo' => true]);
        $notifOutra = makeNotif($outra);

        $this->actingAs($this->user)
            ->deleteJson(route('notificacoes.destroy', $notifOutra))
            ->assertNotFound();

        expect(Notificacao::withoutGlobalScope('company')->find($notifOutra->id))->not->toBeNull();
    });

    it('unread-count retorna zero quando não há não lidas', function () {
        makeNotif($this->company);
        Notificacao::where('company_id', $this->company->id)->update(['read_at' => now()]);

        $this->actingAs($this->user)
            ->getJson(route('notificacoes.unread-count'))
            ->assertOk()
            ->assertJson(['count' => 0]);
    });

    it('unread-count retorna contagem correta', function () {
        makeNotif($this->company);
        makeNotif($this->company);
        $lida = makeNotif($this->company);
        $lida->update(['read_at' => now()]);

        $this->actingAs($this->user)
            ->getJson(route('notificacoes.unread-count'))
            ->assertOk()
            ->assertJson(['count' => 2]);
    });

    it('unread-count é isolado por empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-count', 'plano' => 'trial', 'ativo' => true]);
        makeNotif($outra);
        makeNotif($outra);

        $this->actingAs($this->user)
            ->getJson(route('notificacoes.unread-count'))
            ->assertOk()
            ->assertJson(['count' => 0]);
    });

    it('unauthenticated é redirecionado', function () {
        $notif = makeNotif($this->company);

        $this->deleteJson(route('notificacoes.destroy', $notif))
            ->assertUnauthorized();
    });
});
