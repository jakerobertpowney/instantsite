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
            'overrides.description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'socials' => ['nullable', 'array'],
            'socials.instagram' => ['nullable', 'string', 'url', 'max:255'],
            'socials.facebook' => ['nullable', 'string', 'url', 'max:255'],
            'socials.x' => ['nullable', 'string', 'url', 'max:255'],
            'socials.linkedin' => ['nullable', 'string', 'url', 'max:255'],
            'logo' => [
                'nullable',
                'file',
                'image',
                'max:2048',
            ],
        ];
    }
}
