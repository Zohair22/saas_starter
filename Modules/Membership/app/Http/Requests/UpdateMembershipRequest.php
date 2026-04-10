<?php

namespace Modules\Membership\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Membership\Enums\MembershipRole;

class UpdateMembershipRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'role' => ['sometimes', Rule::in(array_column(MembershipRole::cases(), 'value'))],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
