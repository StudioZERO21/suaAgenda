<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Company;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'barbearia-teste')->firstOrFail();

        $clientes = [
            ['name' => 'Lucas Oliveira',     'phone' => '(11) 98765-1001', 'email' => 'lucas.oliveira@email.com',  'data_nasc' => '1990-03-14'],
            ['name' => 'Rafael Mendes',      'phone' => '(11) 99123-2002', 'email' => 'rafael.mendes@email.com',   'data_nasc' => '1985-07-22'],
            ['name' => 'Pedro Costa',        'phone' => '(11) 97654-3003', 'email' => null,                         'data_nasc' => '1995-11-08'],
            ['name' => 'Gabriel Santos',     'phone' => '(11) 96543-4004', 'email' => 'gabriel.s@email.com',       'data_nasc' => '1992-05-30'],
            ['name' => 'Mateus Alves',       'phone' => '(11) 95432-5005', 'email' => 'mateus.alves@email.com',    'data_nasc' => '1988-09-17'],
            ['name' => 'Fernando Lima',      'phone' => '(11) 94321-6006', 'email' => null,                         'data_nasc' => '1998-01-25'],
            ['name' => 'Thiago Rocha',       'phone' => '(11) 93210-7007', 'email' => 'thiago.rocha@email.com',    'data_nasc' => '1993-12-03'],
            ['name' => 'Bruno Ferreira',     'phone' => '(11) 92109-8008', 'email' => 'bruno.f@email.com',         'data_nasc' => '1987-04-11'],
            ['name' => 'Diego Carvalho',     'phone' => '(11) 91098-9009', 'email' => null,                         'data_nasc' => '1996-08-19'],
            ['name' => 'Henrique Ribeiro',   'phone' => '(11) 90987-0010', 'email' => 'henrique.r@email.com',      'data_nasc' => '1991-02-28'],
            ['name' => 'Anderson Martins',   'phone' => '(11) 89876-1011', 'email' => 'anderson.m@email.com',      'data_nasc' => '1984-06-15'],
            ['name' => 'Rodrigo Pires',      'phone' => '(11) 88765-2012', 'email' => null,                         'data_nasc' => '1997-10-07'],
            ['name' => 'Leandro Nascimento', 'phone' => '(11) 87654-3013', 'email' => 'leandro.n@email.com',       'data_nasc' => '1989-03-21'],
            ['name' => 'Fábio Teixeira',     'phone' => '(11) 86543-4014', 'email' => 'fabio.t@email.com',         'data_nasc' => '1994-07-09'],
            ['name' => 'Marcos Souza',       'phone' => '(11) 85432-5015', 'email' => null,                         'data_nasc' => '1986-11-30'],
        ];

        foreach ($clientes as $data) {
            Cliente::firstOrCreate(
                ['company_id' => $company->id, 'name' => $data['name']],
                [
                    ...$data,
                    'company_id' => $company->id,
                    'lgpd_consent' => true,
                ]
            );
        }

        $this->command->info('✓ 15 clientes criados para Barbearia Teste.');
    }
}
