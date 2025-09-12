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
            'logo' => ['nullable', 'file', 'mimes:jpg,png'],
            'description' => ['required', 'string'],
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
            'quicklinks' => [
                'nullable',
                'array'
            ],
            'quicklinks.*.label' => [
                'required',
                'string'
            ],
            'quicklinks.*.link' => [
                'required',
                'url:http,https'
            ]
        ];
    }
}
