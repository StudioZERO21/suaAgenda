<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTipografiaConfiguracaoRequest extends FormRequest
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
            'heading_font' => ['required', 'string', 'in:poppins,montserrat,jakarta,dm-serif'],
            'body_font' => ['required', 'string', 'in:inter,dm-sans,nunito,lato'],
        ];
    }
}
