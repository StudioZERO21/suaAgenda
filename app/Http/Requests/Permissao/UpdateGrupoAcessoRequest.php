<?php

declare(strict_types=1);

namespace App\Http\Requests\Permissao;

use App\Support\SaDemoData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGrupoAcessoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin_empresa', 'super_admin'])
            || $this->user()->can('cfg_perms');
    }

    public function rules(): array
    {
        return [
            'nome' => [
                'required', 'string', 'max:60',
                Rule::unique('roles', 'name')
                    ->where('company_id', $this->user()->empresa_id)
                    ->where('guard_name', 'web')
                    ->ignore($this->route('grupo')),
            ],
            'cor' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'descricao' => ['nullable', 'string', 'max:200'],
            'perms' => ['present', 'array'],
            'perms.*' => ['string', Rule::in(array_keys(SaDemoData::permissionsFlat()))],
        ];
    }
}
