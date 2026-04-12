<?php

namespace Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscribeTenantRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'plan_code' => ['required', 'string', Rule::exists('plans', 'code')->where('is_active', true)],
            'payment_method' => ['nullable', 'string', 'starts_with:pm_'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
