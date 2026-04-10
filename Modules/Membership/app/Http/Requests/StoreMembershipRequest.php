<?php

namespace Modules\Membership\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Membership\Enums\MembershipRole;

class StoreMembershipRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', Rule::in(array_column(MembershipRole::cases(), 'value'))],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
