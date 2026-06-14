<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmpresaConfiguracaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Company $company */
        $company = $this->route('company') ?? Company::find($this->user()->empresa_id);

        return $company && $this->user()->can('update', $company);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()->empresa_id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'segment' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:300'],
            'instagram' => ['nullable', 'string', 'max:80'],
            'facebook' => ['nullable', 'string', 'max:80'],
            'tiktok' => ['nullable', 'string', 'max:80'],
            'youtube' => ['nullable', 'string', 'max:80'],
            'slug' => [
                'required', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('companies', 'slug')->ignore($companyId),
            ],
            'lgpd_consent' => ['boolean'],
            'hours' => ['nullable', 'array'],
            'hours.*.status' => ['nullable', 'string', 'in:aberto,fechado,ferias,feriado,reforma,outro'],
            'hours.*.open' => ['nullable', 'date_format:H:i'],
            'hours.*.close' => ['nullable', 'date_format:H:i'],
            'hours.*.return_date' => ['nullable', 'date'],
            'closure' => ['nullable', 'array'],
            'closure.active' => ['boolean'],
            'closure.status' => ['nullable', 'string', 'in:ferias,feriado,reforma,outro'],
            'closure.return_date' => ['nullable', 'date'],
            'closure.note' => ['nullable', 'string', 'max:200'],
            'min_advance_mins' => ['nullable', 'integer', 'in:0,30,60,120,1440'],
            'max_advance_days' => ['nullable', 'integer', 'in:7,15,30,60,90'],
            'confirm_required' => ['boolean'],
            'auto_reminder' => ['boolean'],
            'reminder_hours' => ['nullable', 'integer', 'in:1,2,24,48'],
            'cancel_policy' => ['nullable', 'string', 'max:500'],
            'pix_key' => ['nullable', 'string', 'max:120'],
            'pix_key_type' => ['nullable', 'string', 'in:random,email,phone,document'],
            'pix_city' => ['nullable', 'string', 'max:60'],
        ];
    }
}
