<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferenciasConfiguracaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = Company::find($this->user()->empresa_id);

        return $company && $this->user()->can('update', $company);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'theme_palette' => ['required', 'string', 'in:A,B,C,D,E,F,G,H,I,J,K,L'],
            'dark_mode' => ['boolean'],
            'notifications' => ['nullable', 'array'],
            'security' => ['nullable', 'array'],
            'contacts' => ['nullable', 'array'],
        ];
    }
}
