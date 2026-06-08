<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Produto;
use Illuminate\Database\Seeder;

class ProdutoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'barbearia-teste')->firstOrFail();

        $produtos = [
            ['nome' => 'Pomada modeladora', 'sku' => 'POB001', 'categoria' => 'Cabelo', 'preco' => 45.90, 'custo' => 22.00, 'estoque' => 18, 'unidade' => 'un.', 'descricao' => 'Fixação forte, efeito matte. 100g'],
            ['nome' => 'Óleo de barba', 'sku' => 'OBB002', 'categoria' => 'Barba', 'preco' => 38.00, 'custo' => 15.00, 'estoque' => 24, 'unidade' => 'un.', 'descricao' => 'Hidratação e brilho para barba. 30ml'],
            ['nome' => 'Shampoo profissional', 'sku' => 'SHP003', 'categoria' => 'Cabelo', 'preco' => 65.00, 'custo' => 28.00, 'estoque' => 12, 'unidade' => 'un.', 'descricao' => 'Shampoo sem sulfato 300ml'],
            ['nome' => 'Cera capilar', 'sku' => 'CRC004', 'categoria' => 'Cabelo', 'preco' => 32.00, 'custo' => 14.00, 'estoque' => 9, 'unidade' => 'un.', 'descricao' => 'Cera para acabamento texturizado. 80g'],
            ['nome' => 'Balm pós-barba', 'sku' => 'BLM005', 'categoria' => 'Barba', 'preco' => 42.00, 'custo' => 18.00, 'estoque' => 15, 'unidade' => 'un.', 'descricao' => 'Hidratante calmante pós-barbear. 50ml'],
            ['nome' => 'Gel fixador', 'sku' => 'GFX006', 'categoria' => 'Cabelo', 'preco' => 22.00, 'custo' => 9.00, 'estoque' => 3, 'unidade' => 'un.', 'descricao' => 'Gel com fixação extra forte. 250ml'],
            ['nome' => 'Navalhete descartável', 'sku' => 'NAV007', 'categoria' => 'Acessórios', 'preco' => 8.50, 'custo' => 3.00, 'estoque' => 50, 'unidade' => 'un.', 'descricao' => 'Navalhete descartável profissional'],
            ['nome' => 'Toalha de barbeiro', 'sku' => 'TOA008', 'categoria' => 'Acessórios', 'preco' => 28.00, 'custo' => 12.00, 'estoque' => 20, 'unidade' => 'un.', 'descricao' => 'Toalha de microfibra 70x40cm'],
            ['nome' => 'Condicionador masculino', 'sku' => 'CON009', 'categoria' => 'Cabelo', 'preco' => 52.00, 'custo' => 22.00, 'estoque' => 7, 'unidade' => 'un.', 'descricao' => 'Condicionador para cabelos masculinos. 200ml'],
            ['nome' => 'Loção hidratante facial', 'sku' => 'LOC010', 'categoria' => 'Skincare', 'preco' => 78.00, 'custo' => 35.00, 'estoque' => 0, 'unidade' => 'un.', 'descricao' => 'Loção facial hidratante. 50ml'],
        ];

        foreach ($produtos as $data) {
            Produto::firstOrCreate(
                ['company_id' => $company->id, 'sku' => $data['sku']],
                array_merge($data, ['company_id' => $company->id, 'estoque_min' => 5, 'ativo' => true])
            );
        }
    }
}
