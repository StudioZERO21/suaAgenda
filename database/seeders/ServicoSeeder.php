<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Database\Seeder;

class ServicoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'barbearia-teste')->firstOrFail();

        $servicos = [
            ['nome' => 'Corte',          'duracao_minutos' => 30,  'preco' => 45.00,  'cor' => '#1a1a1a', 'icone' => 'scissors',         'categoria' => 'Cabelo'],
            ['nome' => 'Barba',          'duracao_minutos' => 30,  'preco' => 35.00,  'cor' => '#d4a574', 'icone' => 'barba_silhueta',   'categoria' => 'Barba'],
            ['nome' => 'Corte + Barba',  'duracao_minutos' => 60,  'preco' => 75.00,  'cor' => '#6366f1', 'icone' => 'barba_tesoura',    'categoria' => 'Combo'],
            ['nome' => 'Hidratação',     'duracao_minutos' => 60,  'preco' => 90.00,  'cor' => '#10b981', 'icone' => 'mulher_mascara',   'categoria' => 'Tratamento'],
            ['nome' => 'Coloração',      'duracao_minutos' => 120, 'preco' => 180.00, 'cor' => '#ec4899', 'icone' => 'mulher_coloracao', 'categoria' => 'Tratamento'],
            ['nome' => 'Barba + Bigode', 'duracao_minutos' => 45,  'preco' => 50.00,  'cor' => '#f59e0b', 'icone' => 'bigode',           'categoria' => 'Barba'],
        ];

        $criados = collect();

        foreach ($servicos as $data) {
            $criados->push(
                Servico::firstOrCreate(
                    ['company_id' => $company->id, 'nome' => $data['nome']],
                    [...$data, 'company_id' => $company->id, 'ativo' => true]
                )
            );
        }

        // Cria 2 profissionais de exemplo (entidades de booking, separadas dos Users)
        $carlos = Profissional::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Carlos Silva'],
            ['especialidade' => 'Barbeiro', 'comissao_pct' => 30.00, 'ativo' => true]
        );

        $joao = Profissional::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'João Barbeiro'],
            ['especialidade' => 'Cabeleireiro', 'comissao_pct' => 25.00, 'ativo' => true]
        );

        // Vincula serviços: Carlos faz corte, barba e combo; João faz tudo
        $corte = $criados->firstWhere('nome', 'Corte');
        $barba = $criados->firstWhere('nome', 'Barba');
        $combo = $criados->firstWhere('nome', 'Corte + Barba');
        $hid = $criados->firstWhere('nome', 'Hidratação');
        $color = $criados->firstWhere('nome', 'Coloração');

        $carlos->servicos()->syncWithoutDetaching([$corte->id, $barba->id, $combo->id]);
        $joao->servicos()->syncWithoutDetaching([$corte->id, $barba->id, $combo->id, $hid->id, $color->id]);

        $this->command->info('✓ 6 serviços e 2 profissionais criados para Barbearia Teste.');
    }
}
