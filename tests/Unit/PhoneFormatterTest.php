<?php

declare(strict_types=1);

use App\Support\PhoneFormatter;

describe('PhoneFormatter', function () {
    it('formata celular com 11 dígitos', function () {
        expect(PhoneFormatter::format('11999998888'))->toBe('(11) 99999-8888');
    });

    it('formata telefone fixo com 10 dígitos', function () {
        expect(PhoneFormatter::format('1133334444'))->toBe('(11) 3333-4444');
    });

    it('remove código do país 55 antes de formatar', function () {
        expect(PhoneFormatter::format('5511999998888'))->toBe('(11) 99999-8888');
    });

    it('normaliza valor mascarado para persistência', function () {
        expect(PhoneFormatter::normalize('(11) 99999-8888'))->toBe('(11) 99999-8888');
    });

    it('retorna null para telefone vazio', function () {
        expect(PhoneFormatter::normalize(''))->toBeNull();
        expect(PhoneFormatter::normalize(null))->toBeNull();
    });

    it('extrai apenas dígitos', function () {
        expect(PhoneFormatter::digits('(11) 99999-8888'))->toBe('11999998888');
    });
});
