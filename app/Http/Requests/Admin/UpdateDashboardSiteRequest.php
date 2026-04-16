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
            'meta_title' => [
                'required',
                'string',
                'max:60'
            ],
            'meta_description' => [
                'required',
                'string',
                'max:158'
            ],
            'domain_type' => [
                'required',
                'in:subdomain,custom,draft'
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
            ]
        ];
    }
}
