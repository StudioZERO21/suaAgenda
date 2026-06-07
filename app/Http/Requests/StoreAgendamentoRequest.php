<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Agendamento;
use Illuminate\Foundation\Http\FormRequest;

class StoreAgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Agendamento::class);
    }

    public function rules(): array
    {
        return [
            'profissional_id' => ['required', 'uuid', 'exists:profissionais,id'],
            'cliente_id' => ['required', 'uuid', 'exists:clientes,id'],
            'servico_id' => ['nullable', 'uuid', 'exists:servicos,id'],
            'data_hora' => ['required', 'date', 'after:now'],
            'duracao' => ['required', 'integer', 'min:15', 'max:480'],
            'valor' => ['nullable', 'numeric', 'min:0'],
            'observacao' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'profissional_id.required' => 'O profissional é obrigatório.',
            'profissional_id.exists' => 'Profissional não encontrado.',
            'cliente_id.required' => 'O cliente é obrigatório.',
            'cliente_id.exists' => 'Cliente não encontrado.',
            'data_hora.required' => 'A data e hora são obrigatórias.',
            'data_hora.after' => 'O agendamento deve ser para uma data futura.',
            'duracao.required' => 'A duração é obrigatória.',
            'duracao.min' => 'A duração mínima é de 15 minutos.',
            'duracao.max' => 'A duração máxima é de 480 minutos (8 horas).',
        ];
    }
}
