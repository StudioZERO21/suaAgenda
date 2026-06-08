<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Cargo;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CargoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'barbearia-teste')->firstOrFail();

        $cargos = [
            ['nome' => 'Administrador', 'nivel' => 'admin', 'cor' => '#ef4444', 'descricao' => 'Acesso total ao sistema.', 'comissao_pct' => null],
            ['nome' => 'Gerente', 'nivel' => 'manager', 'cor' => '#f59e0b', 'descricao' => 'Gerencia equipe e relatórios.', 'comissao_pct' => null],
            ['nome' => 'Barbeiro', 'nivel' => 'professional', 'cor' => '#1a1a1a', 'descricao' => 'Realiza atendimentos e vê sua agenda.', 'comissao_pct' => 40.00],
            ['nome' => 'Recepcionista', 'nivel' => 'receptionist', 'cor' => '#10b981', 'descricao' => 'Gerencia agendamentos e clientes.', 'comissao_pct' => null],
        ];

        foreach ($cargos as $data) {
            Cargo::firstOrCreate(
                ['company_id' => $company->id, 'nome' => $data['nome']],
                array_merge($data, ['company_id' => $company->id])
            );
        }
    }
}
