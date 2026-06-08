<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgendamentoPublicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profissional_id' => ['required', 'uuid', 'exists:profissionais,id'],
            'servico_id' => ['required', 'uuid', 'exists:servicos,id'],
            'data_hora' => ['required', 'date', 'after:now'],
            'cliente_nome' => ['required', 'string', 'max:100'],
            'cliente_phone' => ['required', 'string', 'max:20'],
            'cliente_email' => ['nullable', 'email', 'max:150'],
            'observacao' => ['nullable', 'string', 'max:500'],
        ];
    }
}
