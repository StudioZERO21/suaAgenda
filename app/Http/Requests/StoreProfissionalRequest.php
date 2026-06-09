<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfissionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'especialidade' => ['nullable', 'string', 'max:100'],
            'comissao_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ativo' => ['boolean'],
            'cor' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'phone' => ['nullable', 'string', 'max:25'],
            'admissao' => ['nullable', 'date'],
            'instagram' => ['nullable', 'string', 'max:100'],
            'tiktok' => ['nullable', 'string', 'max:100'],
            'facebook' => ['nullable', 'string', 'max:150'],
            'servicos' => ['nullable', 'array'],
            'servicos.*' => ['uuid', 'exists:servicos,id'],
        ];
    }
}
