<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'required', 'string', 'max:100'],
            'descricao' => ['nullable', 'string', 'max:500'],
            'duracao_minutos' => ['sometimes', 'required', 'integer', 'min:5', 'max:480'],
            'preco' => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999.99'],
            'categoria' => ['nullable', 'string', 'max:60'],
            'cor' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ativo' => ['boolean'],
            'profissionais' => ['nullable', 'array'],
            'profissionais.*' => ['uuid', 'exists:profissionais,id'],
        ];
    }
}
