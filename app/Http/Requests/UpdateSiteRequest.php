<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->company !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:255'],
            'subheadline' => ['nullable', 'string', 'max:500'],
            'cta_text' => ['nullable', 'string', 'max:100'],
            'cta_secondary' => ['nullable', 'string', 'max:100'],
            'show_stats' => ['nullable', 'boolean'],
            'stats_items' => ['nullable', 'array', 'max:6'],
            'stats_items.*.n' => ['string', 'max:20'],
            'stats_items.*.l' => ['string', 'max:60'],
            'show_services' => ['nullable', 'boolean'],
            'show_portfolio' => ['nullable', 'boolean'],
            'show_team' => ['nullable', 'boolean'],
            'show_testimonials' => ['nullable', 'boolean'],
            'show_store' => ['nullable', 'boolean'],
            'show_booking_cta' => ['nullable', 'boolean'],
            'show_map' => ['nullable', 'boolean'],
            'confirmation_msg' => ['nullable', 'string', 'max:500'],
            'reminder_msg' => ['nullable', 'string', 'max:500'],
            'cancellation_msg' => ['nullable', 'string', 'max:500'],
            'lgpd_msg' => ['nullable', 'string', 'max:500'],
            'welcome_popup' => ['nullable', 'string', 'max:1000'],
            'footer_text' => ['nullable', 'string', 'max:200'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_desc' => ['nullable', 'string', 'max:160'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'google_analytics' => ['nullable', 'string', 'max:50'],
        ];
    }
}
