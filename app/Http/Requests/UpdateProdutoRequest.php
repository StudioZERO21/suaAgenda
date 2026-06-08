<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin_empresa', 'gestor']) ?? false;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'preco' => ['required', 'numeric', 'min:0'],
            'custo' => ['nullable', 'numeric', 'min:0'],
            'estoque' => ['required', 'integer', 'min:0'],
            'estoque_min' => ['required', 'integer', 'min:0'],
            'unidade' => ['nullable', 'string', 'max:20'],
            'descricao' => ['nullable', 'string', 'max:1000'],
            'ativo' => ['nullable', 'boolean'],
        ];
    }
}
