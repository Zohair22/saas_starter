<?php

namespace Modules\Membership\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Membership\Enums\MembershipRole;

class StoreInvitationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'role' => ['required', Rule::in(array_column(MembershipRole::cases(), 'value'))],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
