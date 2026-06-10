<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDashboardComponentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

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
            'components' => [
                'nullable',
                'array',
            ],
            'components.header.enabled' => ['nullable', 'boolean'],
            'components.description.enabled' => ['nullable', 'boolean'],
            'components.gallery.enabled' => ['nullable', 'boolean'],
            'components.quick_actions.enabled' => ['nullable', 'boolean'],
            'components.reviews.enabled' => ['nullable', 'boolean'],
            'components.contact.enabled' => ['nullable', 'boolean'],
            'components.contact_form.enabled' => ['nullable', 'boolean'],
            'components.services.enabled'     => ['nullable', 'boolean'],
            'overrides.description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'overrides.contact_email' => [
                'nullable',
                'email',
                'max:255',
            ],
            // Business identity
            'business_name'     => ['nullable', 'string', 'max:255'],
            'business_type'     => ['nullable', 'string', 'max:255'],
            'city'              => ['nullable', 'string', 'max:100'],
            'region'            => ['nullable', 'string', 'max:100'],
            'phone'             => ['nullable', 'string', 'max:30'],
            'formatted_address' => ['nullable', 'string', 'max:500'],
            // Opening hours
            'opening_hours'           => ['nullable', 'array'],
            'opening_hours.*.day'     => ['nullable', 'string', 'max:10'],
            'opening_hours.*.open'    => ['nullable', 'string', 'max:10'],
            'opening_hours.*.close'   => ['nullable', 'string', 'max:10'],
            'opening_hours.*.closed'  => ['nullable'],
            // Reviews
            'rating'                => ['nullable', 'numeric', 'min:0', 'max:5'],
            'review_count'          => ['nullable', 'integer', 'min:0'],
            'reviews'               => ['nullable', 'array', 'max:50'],
            'reviews.*.id'          => ['nullable', 'string', 'max:40'],
            'reviews.*.author'      => ['nullable', 'string', 'max:100'],
            'reviews.*.text'        => ['nullable', 'string', 'max:1000'],
            'reviews.*.rating'      => ['nullable', 'integer', 'min:1', 'max:5'],
            'reviews.*.date'        => ['nullable', 'string', 'max:30'],
            // Photo uploads
            'photos'      => ['nullable', 'array'],
            'photos.*'    => ['nullable', 'file', 'image', 'max:10240'],
            'remove_photos'   => ['nullable', 'array'],
            'remove_photos.*' => ['nullable', 'string', 'max:500'],
            'socials' => ['nullable', 'array'],
            'socials.instagram' => ['nullable', 'string', 'url', 'max:255'],
            'socials.facebook' => ['nullable', 'string', 'url', 'max:255'],
            'socials.x' => ['nullable', 'string', 'url', 'max:255'],
            'socials.linkedin' => ['nullable', 'string', 'url', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'digits_between:7,15'],
            'quickLinks' => ['nullable', 'array', 'max:10'],
            'quickLinks.*.label' => ['required_with:quickLinks.*', 'string', 'max:60'],
            'quickLinks.*.link' => ['required_with:quickLinks.*', 'string', 'url', 'max:255'],
            'logo' => [
                'nullable',
                'file',
                'image',
                'max:2048',
            ],
            // Custom colour palette
            'palette_primary'   => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'palette_secondary' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            // Services
            'services'                    => ['nullable', 'array', 'max:50'],
            'services.*.id'               => ['nullable', 'string', 'max:40'],
            'services.*.name'             => ['required_with:services.*', 'string', 'max:120'],
            'services.*.description'      => ['nullable', 'string', 'max:500'],
            'services.*.price'            => ['nullable', 'string', 'max:60'],
            'services.*.currency'         => ['nullable', 'string', 'size:3'],
            'services.*.show_price'       => ['nullable', 'boolean'],
            'services.*.featured'         => ['nullable', 'boolean'],
            'services_heading'            => ['nullable', 'string', 'max:80'],
            'services_cta_label'          => ['nullable', 'string', 'max:60'],
            'services_cta_link'           => ['nullable', 'string', 'url', 'max:255'],
            // Header background fields
            'header_bg_type'       => ['nullable', 'string', 'in:auto,google_image,custom_image,color,stock'],
            'header_bg_value'      => ['nullable', 'string', 'max:500'],
            'header_bg_thumb'      => ['nullable', 'string', 'max:500'],
            'header_bg_credit'     => ['nullable', 'string', 'max:255'],
            'header_bg_credit_url' => ['nullable', 'string', 'url', 'max:500'],
            'header_bg_image'      => ['nullable', 'file', 'image', 'max:5120'],
            // Favicon
            'favicon_type' => ['nullable', 'string', 'in:upload,logo,initials,clear'],
            'favicon'      => ['nullable', 'file', 'image', 'max:512'],
            // Photo reorder
            'images_order'   => ['nullable', 'array'],
            'images_order.*' => ['nullable', 'string', 'max:500'],
        ];
    }
}
