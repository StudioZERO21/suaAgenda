<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCargoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin_empresa']) ?? false;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'nivel' => ['required', 'string', 'max:50'],
            'cor' => ['nullable', 'string', 'max:20'],
            'descricao' => ['nullable', 'string', 'max:500'],
            'comissao' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
