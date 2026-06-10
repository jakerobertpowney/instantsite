<?php

namespace App\Http\Requests\Setup;

use Illuminate\Foundation\Http\FormRequest;

class StoreSetupRequest extends FormRequest
{
    /**
     * Normalise fields before validation runs.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('whatsapp_number')) {
            $raw = $this->input('whatsapp_number', '');
            $digits = is_string($raw) ? preg_replace('/\D/', '', $raw) : '';
            $this->merge(['whatsapp_number' => $digits ?: null]);
        }

        if ($this->has('phone')) {
            $raw = $this->input('phone', '');
            $normalised = is_string($raw) ? str_replace(' ', '', $raw) : '';
            $this->merge(['phone' => $normalised ?: null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_name'          => ['nullable', 'string', 'max:255'],
            'business_type'          => ['nullable', 'string', 'max:255'],
            'formatted_address'      => ['nullable', 'string', 'max:500'],
            'city'                   => ['nullable', 'string', 'max:255'],
            'region'                 => ['nullable', 'string', 'max:255'],
            'phone'                  => ['nullable', 'string', 'max:50'],
            'whatsapp_number'        => ['nullable', 'string', 'digits_between:7,15'],
            'website_url'            => ['nullable', 'url:http,https'],
            'logo'                   => ['nullable', 'file', 'mimes:jpg,jpeg,png'],
            'suggested_logo_url'     => ['nullable', 'url:https'],
            'description'            => ['nullable', 'string', 'max:2000'],
            'socials'                => ['nullable', 'array'],
            'socials.*'              => ['nullable', 'string', 'url:http,https'],
            'contact'                => ['nullable', 'string', 'email'],
            'quickLinks'             => ['nullable', 'array'],
            'quickLinks.*.label'     => ['required_with:quickLinks', 'string'],
            'quickLinks.*.link'      => ['required_with:quickLinks', 'url:http,https'],
            'opening_hours'          => ['nullable', 'array'],
            'opening_hours.*.day'    => ['required_with:opening_hours', 'string'],
            'opening_hours.*.open'   => ['nullable', 'string'],
            'opening_hours.*.close'  => ['nullable', 'string'],
            'opening_hours.*.closed' => ['nullable', 'boolean'],
            'photos'                 => ['nullable', 'array', 'max:20'],
            'photos.*'               => ['file', 'image', 'mimes:jpg,jpeg,png,webp,heic', 'max:10240'],
            'remove_photos'          => ['nullable', 'array'],
            'remove_photos.*'        => ['string'],
            'services'               => ['nullable', 'array'],
            'rating'                 => ['nullable', 'numeric', 'min:0', 'max:5'],
            'review_count'           => ['nullable', 'integer', 'min:0'],
            'reviews'                => ['nullable', 'array'],
            'reviews.*.author'       => ['required_with:reviews', 'string', 'max:255'],
            'reviews.*.text'         => ['required_with:reviews', 'string', 'max:2000'],
            'reviews.*.rating'       => ['nullable', 'integer', 'min:1', 'max:5'],
            'reviews.*.date'         => ['nullable', 'string', 'max:20'],
            'premium'                => ['nullable', 'boolean'],
        ];
    }
}
