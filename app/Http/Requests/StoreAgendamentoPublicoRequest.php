<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgendamentoPublicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = Company::where('slug', $this->route('slug'))->value('id');

        return [
            'profissional_id' => [
                'required',
                'uuid',
                Rule::exists('profissionais', 'id')
                    ->where('company_id', $companyId)
                    ->where('ativo', true)
                    ->whereNull('deleted_at'),
            ],
            'servico_id' => [
                'required',
                'uuid',
                Rule::exists('servicos', 'id')
                    ->where('company_id', $companyId)
                    ->where('ativo', true)
                    ->whereNull('deleted_at'),
            ],
            'data_hora' => ['required', 'date', 'after:now'],
            'cliente_nome' => ['required', 'string', 'max:100'],
            'cliente_phone' => ['required', 'string', 'max:20'],
            'cliente_email' => ['nullable', 'email', 'max:150'],
            'observacao' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'profissional_id.exists' => 'Profissional inválido para este estabelecimento.',
            'servico_id.exists' => 'Serviço inválido para este estabelecimento.',
        ];
    }
}
