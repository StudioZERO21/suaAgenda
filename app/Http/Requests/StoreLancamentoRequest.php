<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLancamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin_empresa', 'gestor']) ?? false;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:receita,despesa'],
            'descricao' => ['required', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'data' => ['required', 'date'],
            'status' => ['required', 'in:pendente,pago,cancelado'],
            'metodo_pagamento' => ['nullable', 'string', 'max:50'],
            'observacao' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
