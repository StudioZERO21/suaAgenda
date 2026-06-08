<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MoveAgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('agendamento'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'data' => ['required', 'date', 'date_format:Y-m-d'],
            'hora' => ['required', 'integer', 'min:0', 'max:23'],
            'minuto' => ['required', 'integer', 'in:0,30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'data.required' => 'A data é obrigatória.',
            'data.date_format' => 'Formato de data inválido.',
            'hora.required' => 'A hora é obrigatória.',
            'hora.min' => 'Hora inválida.',
            'hora.max' => 'Hora inválida.',
            'minuto.in' => 'O horário deve ser em intervalos de 30 minutos.',
        ];
    }

    /**
     * Garante que o novo horário não esteja no passado.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $dataHora = Carbon::parse(sprintf(
                '%s %02d:%02d',
                $this->input('data'),
                (int) $this->input('hora'),
                (int) $this->input('minuto'),
            ));

            if ($dataHora->isPast()) {
                $v->errors()->add('data_hora', 'O agendamento deve ser para um horário futuro.');
            }
        });
    }

    /**
     * Retorna o novo instante do agendamento.
     */
    public function dataHora(): Carbon
    {
        return Carbon::parse(sprintf(
            '%s %02d:%02d',
            $this->validated('data'),
            (int) $this->validated('hora'),
            (int) $this->validated('minuto'),
        ));
    }
}
