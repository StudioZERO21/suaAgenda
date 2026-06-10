<?php

declare(strict_types=1);

use App\Mail\ResetCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['email' => 'reset@example.com', 'password' => Hash::make('senha-antiga')]);
});

describe('password_reset', function () {
    it('envia código quando email existe', function () {
        Mail::fake();

        $this->postJson(route('password.send-code'), ['email' => 'reset@example.com'])
            ->assertOk()
            ->assertJson(['success' => true]);

        Mail::assertQueued(ResetCodeMail::class, fn ($m) => $m->hasTo('reset@example.com'));

        expect(DB::table('password_reset_tokens')->where('email', 'reset@example.com')->exists())->toBeTrue();
    });

    it('retorna 200 mesmo quando email não existe (anti-enumeração)', function () {
        Mail::fake();

        $this->postJson(route('password.send-code'), ['email' => 'naoexiste@example.com'])
            ->assertOk()
            ->assertJson(['success' => true]);

        Mail::assertNothingQueued();
    });

    it('verifica código válido', function () {
        $code = '123456';
        DB::table('password_reset_tokens')->insert([
            'email' => 'reset@example.com',
            'token' => Hash::make($code),
            'created_at' => now(),
        ]);

        $this->postJson(route('password.verify-code'), ['email' => 'reset@example.com', 'code' => $code])
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('rejeita código errado', function () {
        DB::table('password_reset_tokens')->insert([
            'email' => 'reset@example.com',
            'token' => Hash::make('999999'),
            'created_at' => now(),
        ]);

        $this->postJson(route('password.verify-code'), ['email' => 'reset@example.com', 'code' => '111111'])
            ->assertStatus(422);
    });

    it('rejeita código expirado', function () {
        $code = '654321';
        DB::table('password_reset_tokens')->insert([
            'email' => 'reset@example.com',
            'token' => Hash::make($code),
            'created_at' => now()->subMinutes(20),
        ]);

        $this->postJson(route('password.verify-code'), ['email' => 'reset@example.com', 'code' => $code])
            ->assertStatus(422);
    });

    it('redefine senha com código válido', function () {
        $code = '246810';
        DB::table('password_reset_tokens')->insert([
            'email' => 'reset@example.com',
            'token' => Hash::make($code),
            'created_at' => now(),
        ]);

        $this->postJson(route('password.reset-custom'), [
            'email' => 'reset@example.com',
            'code' => $code,
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ])->assertOk()->assertJson(['success' => true]);

        expect(Hash::check('nova-senha-123', $this->user->fresh()->password))->toBeTrue();
        expect(DB::table('password_reset_tokens')->where('email', 'reset@example.com')->exists())->toBeFalse();
    });

    it('não redefine com código expirado no step 3', function () {
        $code = '135790';
        DB::table('password_reset_tokens')->insert([
            'email' => 'reset@example.com',
            'token' => Hash::make($code),
            'created_at' => now()->subMinutes(20),
        ]);

        $this->postJson(route('password.reset-custom'), [
            'email' => 'reset@example.com',
            'code' => $code,
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ])->assertStatus(422);

        expect(Hash::check('senha-antiga', $this->user->fresh()->password))->toBeTrue();
    });

    it('página de recuperação renderiza corretamente', function () {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Recuperar conta');
    });
});
