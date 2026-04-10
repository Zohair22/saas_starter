<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateApiTokenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['sometimes', 'array'],
            'abilities.*' => ['string', 'max:100'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
