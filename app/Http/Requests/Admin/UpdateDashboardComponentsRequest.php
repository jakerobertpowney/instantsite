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
            'overrides.hidden_reviews' => ['nullable', 'array'],
            'overrides.hidden_reviews.*' => ['integer', 'min:0'],
            'socials' => ['nullable', 'array'],
            'socials.instagram' => ['nullable', 'string', 'url', 'max:255'],
            'socials.facebook' => ['nullable', 'string', 'url', 'max:255'],
            'socials.x' => ['nullable', 'string', 'url', 'max:255'],
            'socials.linkedin' => ['nullable', 'string', 'url', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
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
            // Header background fields
            'header_bg_type'       => ['nullable', 'string', 'in:auto,google_image,custom_image,color,stock'],
            'header_bg_value'      => ['nullable', 'string', 'max:500'],
            'header_bg_thumb'      => ['nullable', 'string', 'max:500'],
            'header_bg_credit'     => ['nullable', 'string', 'max:255'],
            'header_bg_credit_url' => ['nullable', 'string', 'url', 'max:500'],
            'header_bg_image'      => ['nullable', 'file', 'image', 'max:5120'],
        ];
    }
}
