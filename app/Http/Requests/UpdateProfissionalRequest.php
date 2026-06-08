<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfissionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'especialidade' => ['nullable', 'string', 'max:100'],
            'comissao_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ativo' => ['boolean'],
            'servicos' => ['nullable', 'array'],
            'servicos.*' => ['uuid', 'exists:servicos,id'],
        ];
    }
}
