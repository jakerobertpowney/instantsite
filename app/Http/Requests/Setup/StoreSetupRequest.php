<?php

namespace App\Http\Requests\Setup;

use Illuminate\Foundation\Http\FormRequest;

class StoreSetupRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'logo' => ['nullable', 'file', 'mimes:jpg,png,gif,webp'],
            'suggested_logo_url' => ['nullable', 'url:https'],
            'description' => ['nullable', 'string', 'max:2000'],
            'socials' => [
                'required',
                'array'
            ],
            'socials.*' => [
                'nullable',
                'string',
                'url:http,https'
            ],
            'premium' => [
                'boolean'
            ],
            'contact' => [
                'nullable',
                'string',
                'email'
            ],
            'quickLinks' => [
                'nullable',
                'array'
            ],
            'quickLinks.*.label' => [
                'required',
                'string'
            ],
            'quickLinks.*.link' => [
                'required',
                'url:http,https'
            ]
        ];
    }
}
