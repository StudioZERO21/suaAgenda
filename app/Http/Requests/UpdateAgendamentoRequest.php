<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('agendamento'));
    }

    public function rules(): array
    {
        return [
            'profissional_id' => ['sometimes', 'uuid', 'exists:profissionais,id'],
            'cliente_id' => ['sometimes', 'uuid', 'exists:clientes,id'],
            'data_hora' => ['sometimes', 'date', 'after:now'],
            'duracao' => ['sometimes', 'integer', 'min:15', 'max:480'],
            'status' => ['sometimes', 'in:pendente,confirmado,finalizado,cancelado'],
            'observacao' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'profissional_id.exists' => 'Profissional não encontrado.',
            'cliente_id.exists' => 'Cliente não encontrado.',
            'data_hora.after' => 'O agendamento deve ser para uma data futura.',
            'duracao.min' => 'A duração mínima é de 15 minutos.',
            'duracao.max' => 'A duração máxima é de 480 minutos (8 horas).',
            'status.in' => 'Status inválido.',
        ];
    }
}
