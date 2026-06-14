<?php

declare(strict_types=1);

use App\Support\CompanyHours;

describe('CompanyHours', function () {
    it('normaliza formato legado com domingo', function () {
        $hours = CompanyHours::normalizeAll([
            'seg' => ['08:00', '20:00', true],
            'dom' => ['10:00', '14:00', false],
        ]);

        expect($hours['seg']['status'])->toBe('aberto')
            ->and($hours['dom']['status'])->toBe('fechado')
            ->and($hours['dom']['open'])->toBe('10:00');
    });

    it('sanitiza fechamento temporário por dia', function () {
        $hours = CompanyHours::sanitizeFromRequest([
            'dom' => [
                'status' => 'ferias',
                'open' => '09:00',
                'close' => '13:00',
                'return_date' => '2026-07-01',
            ],
        ]);

        expect($hours['dom']['status'])->toBe('ferias')
            ->and($hours['dom']['return_date'])->toBe('2026-07-01');
    });

    it('remove data de retorno quando status é fechado semanal', function () {
        $hours = CompanyHours::sanitizeFromRequest([
            'seg' => [
                'status' => 'fechado',
                'return_date' => '2026-07-01',
            ],
        ]);

        expect($hours['seg']['return_date'])->toBeNull();
    });

    it('sanitiza fechamento global do estabelecimento', function () {
        $closure = CompanyHours::sanitizeClosure([
            'active' => '1',
            'status' => 'reforma',
            'return_date' => '2026-08-01',
            'note' => 'Obra na recepção',
        ]);

        expect($closure['active'])->toBeTrue()
            ->and($closure['status'])->toBe('reforma')
            ->and($closure['return_date'])->toBe('2026-08-01')
            ->and($closure['note'])->toBe('Obra na recepção');
    });

    it('retorna expediente quando dia está aberto', function () {
        $exp = CompanyHours::expedienteNaData([
            'hours' => ['seg' => ['status' => 'aberto', 'open' => '09:00', 'close' => '18:00']],
        ], \Carbon\Carbon::parse('2026-06-15'));

        expect($exp)->toBe(['inicio' => '09:00', 'fim' => '18:00']);
    });

    it('retorna null quando fechamento global está ativo', function () {
        $exp = CompanyHours::expedienteNaData([
            'hours' => ['seg' => ['status' => 'aberto', 'open' => '09:00', 'close' => '18:00']],
            'closure' => ['active' => true, 'status' => 'ferias', 'return_date' => '2026-07-01'],
        ], \Carbon\Carbon::parse('2026-06-15'));

        expect($exp)->toBeNull();
    });
});
