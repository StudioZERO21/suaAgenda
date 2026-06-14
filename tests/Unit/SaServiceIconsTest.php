<?php

declare(strict_types=1);

use App\Support\SaServiceIcons;

describe('SaServiceIcons', function () {
    it('normaliza ícone inválido para padrão genérico', function () {
        expect(SaServiceIcons::normalize('invalido'))->toBe('servico_generico');
        expect(SaServiceIcons::normalize(null))->toBe('servico_generico');
    });

    it('formata labels pictográficos de barbearia e salão', function () {
        expect(SaServiceIcons::label('barber_pole'))->toBe('Poste de barbearia');
        expect(SaServiceIcons::label('barba_silhueta'))->toBe('Barba');
        expect(SaServiceIcons::label('mulher_corte'))->toBe('Corte feminino');
        expect(SaServiceIcons::label('mulher_coloracao'))->toBe('Coloração / tintura');
    });

    it('expõe segmentos barbearia e cabeleireiro separados', function () {
        $cats = SaServiceIcons::categories();
        expect($cats['barbearia']['icons'])->toContain('barber_pole');
        expect($cats['barbearia']['icons'])->toContain('navalha');
        expect($cats['barbearia']['icons'])->not->toContain('mulher_corte');
        expect($cats['cabeleireiro']['icons'])->toContain('mulher_corte');
        expect($cats['cabeleireiro']['icons'])->toContain('mulher_mascara');
        expect($cats['cabeleireiro']['icons'])->not->toContain('barber_pole');
    });

    it('resolve segmento a partir do ícone', function () {
        expect(SaServiceIcons::categoryForIcon('barba_silhueta'))->toBe('barbearia');
        expect(SaServiceIcons::categoryForIcon('mulher_coloracao'))->toBe('cabeleireiro');
        expect(SaServiceIcons::categoryForIcon('scissors'))->toBe('barbearia');
        expect(SaServiceIcons::categoryForIcon('paw'))->toBe('pet');
    });

    it('prioriza asset SVG para barbearia e cabeleireiro', function () {
        expect(SaServiceIcons::hasAsset('barber_pole'))->toBeTrue();
        expect(SaServiceIcons::hasAsset('mulher_corte'))->toBeTrue();
        expect(SaServiceIcons::hasAsset('paw'))->toBeFalse();

        expect(SaServiceIcons::assetUrl('barba_silhueta'))
            ->toContain('/assets/icons/servicos/barba_silhueta.svg');
    });
});
