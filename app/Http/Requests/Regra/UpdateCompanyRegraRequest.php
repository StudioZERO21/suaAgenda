<?php

declare(strict_types=1);

namespace App\Http\Requests\Regra;

use App\Models\RegraCatalogo;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Ativação/configuração de uma regra pela empresa.
 * Os params são validados dinamicamente contra o params_schema do catálogo.
 */
class UpdateCompanyRegraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin_empresa', 'super_admin'])
            || $this->user()->can('cfg_rules');
    }

    public function rules(): array
    {
        $regras = [
            'ativo' => ['required', 'boolean'],
            'params' => ['nullable', 'array'],
        ];

        $catalogo = RegraCatalogo::where('codigo', $this->route('codigo'))->first();

        foreach ($catalogo?->params_schema ?? [] as $campo) {
            $regraCampo = match ($campo['type']) {
                'number' => array_filter([
                    'numeric',
                    isset($campo['min']) ? 'min:'.$campo['min'] : null,
                    isset($campo['max']) ? 'max:'.$campo['max'] : null,
                ]),
                'boolean' => ['boolean'],
                default => ['string', 'max:200'],
            };

            $regras['params.'.$campo['key']] = ['nullable', ...$regraCampo];
        }

        return $regras;
    }
}
