<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'lgpd_consent' => ['boolean'],
        ];
    }
}
