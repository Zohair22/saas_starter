<?php

namespace Modules\Membership\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcceptInvitationRequest extends FormRequest
{
    public function rules(): array
    {
        $guestAcceptance = $this->user('sanctum') === null;

        return [
            'name' => [Rule::requiredIf($guestAcceptance), 'nullable', 'string', 'max:255'],
            'password' => [Rule::requiredIf($guestAcceptance), 'nullable', 'string', 'min:8'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
