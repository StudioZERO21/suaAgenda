<?php

declare(strict_types=1);

use App\Mail\RelatorioSemanal;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Rel', 'slug' => 'barbearia-rel',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create([
        'empresa_id' => $this->company->id,
        'email' => 'admin@barbearia.test',
    ]);
    $this->admin->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001']);
});

function makeSemanalAg($self, string $status = 'finalizado', float $valor = 100.0, int $diasAtras = 3): void
{
    Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->subDays($diasAtras),
        'duracao' => 30,
        'status' => $status,
        'valor' => $valor,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('relatorio_semanal_command', function () {
    it('envia email para admin_empresa de cada empresa ativa', function () {
        Mail::fake();
        makeSemanalAg($this);

        $this->artisan('relatorio:semanal')->assertSuccessful();

        Mail::assertQueued(RelatorioSemanal::class, fn ($m) => $m->hasTo('admin@barbearia.test'));
    });

    it('não envia para empresa sem admin_empresa com email', function () {
        Mail::fake();

        $outra = Company::create(['name' => 'Sem Admin', 'slug' => 'sem-admin', 'plano' => 'trial', 'ativo' => true]);

        $this->artisan('relatorio:semanal')->assertSuccessful();

        Mail::assertQueued(RelatorioSemanal::class, 1);
    });

    it('não envia para empresa inativa', function () {
        Mail::fake();

        $inativa = Company::create(['name' => 'Inativa', 'slug' => 'inativa-rel', 'plano' => 'trial', 'ativo' => false]);
        $adminInativa = User::factory()->create(['empresa_id' => $inativa->id, 'email' => 'admin@inativa.test']);
        $adminInativa->assignRole('admin_empresa');

        $this->artisan('relatorio:semanal')->assertSuccessful();

        Mail::assertQueued(RelatorioSemanal::class, fn ($m) => ! $m->hasTo('admin@inativa.test'));
    });

    it('stats contêm receita correta dos finalizados', function () {
        Mail::fake();
        makeSemanalAg($this, 'finalizado', 200.0);
        makeSemanalAg($this, 'finalizado', 100.0);
        makeSemanalAg($this, 'pendente', 300.0);

        $this->artisan('relatorio:semanal')->assertSuccessful();

        Mail::assertQueued(RelatorioSemanal::class, function ($m) {
            return $m->stats['receita'] === 300.0
                && $m->stats['finalizados'] === 2
                && $m->stats['total'] === 3;
        });
    });

    it('email subject contém nome da empresa', function () {
        Mail::fake();

        $this->artisan('relatorio:semanal')->assertSuccessful();

        Mail::assertQueued(RelatorioSemanal::class, function (RelatorioSemanal $m) {
            return str_contains($m->envelope()->subject, 'Barbearia Rel');
        });
    });

    it('template markdown pode ser renderizado sem erro', function () {
        $stats = [
            'total' => 5,
            'finalizados' => 3,
            'receita' => 450.0,
            'ticket_medio' => 150.0,
            'top_servico' => 'Corte',
            'top_profissional' => 'Carlos',
        ];

        $mailable = new RelatorioSemanal($this->company, $stats);
        $rendered = $mailable->render();

        expect($rendered)->toContain('Barbearia Rel')
            ->and($rendered)->toContain('450');
    });
});
