<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDashboardSiteRequest extends FormRequest
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
            'domain_type' => [
                'required',
                'in:subdomain,custom,draft',
                // A custom domain is a paid-plan feature — reject it for users
                // who aren't subscribed, regardless of what the UI allows.
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === 'custom' && ! auth()->user()?->subscribed('default')) {
                        $fail('A custom domain is only available on the paid plan. Please upgrade to continue.');
                    }
                },
            ],
            'subdomain' => [
                'required_if:domain_type,subdomain',
                'nullable',
                'string',
                'not_regex:/\./'
            ],
            'custom_domain' => [
                'required_if:domain_type,custom',
                'nullable',
                'string'
            ],
            'is_private' => [
                'boolean',
            ],
            // Favicon
            'favicon_type' => ['nullable', 'string', 'in:upload,logo,initials,clear'],
            'favicon'      => ['nullable', 'file', 'image', 'max:512'],
            'favicon_data' => ['nullable', 'string', 'max:200000'], // base64 PNG data URL or logo URL
        ];
    }
}
