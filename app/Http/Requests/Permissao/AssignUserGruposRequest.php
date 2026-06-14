<?php

declare(strict_types=1);

namespace App\Http\Requests\Permissao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignUserGruposRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin_empresa', 'super_admin'])
            || $this->user()->can('cfg_perms');
    }

    public function rules(): array
    {
        return [
            'grupo_ids' => ['present', 'array'],
            'grupo_ids.*' => [
                'integer',
                Rule::exists('roles', 'id')
                    ->where('company_id', $this->user()->empresa_id)
                    ->where('guard_name', 'web'),
            ],
        ];
    }
}
