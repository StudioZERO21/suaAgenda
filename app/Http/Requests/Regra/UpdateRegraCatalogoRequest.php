<?php

declare(strict_types=1);

namespace App\Http\Requests\Regra;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegraCatalogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:120'],
            'descricao' => ['nullable', 'string', 'max:300'],
            'categoria' => ['required', 'string', 'max:60'],
            'ativo' => ['boolean'],
            'params_schema' => ['required', 'array', 'min:1'],
            'params_schema.*.key' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/'],
            'params_schema.*.label' => ['required', 'string', 'max:120'],
            'params_schema.*.type' => ['required', Rule::in(['number', 'boolean', 'text'])],
            'params_schema.*.min' => ['nullable', 'numeric'],
            'params_schema.*.max' => ['nullable', 'numeric'],
            'params_default' => ['required', 'array'],
        ];
    }
}
