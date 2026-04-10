<?php

namespace App\Http\Requests\UsersInfo;

use App\Enums\GenderEnum;
use App\Http\Requests\AppRequest;
use App\Rules\NameRule;
use App\Rules\UsernameRule;
use Illuminate\Validation\Rules\Enum;

class CheckUsernameRequest extends AppRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required','string', 'max:'.USERNAME_LENGTH, new UsernameRule]
        ];
    }

    public function messages()
    {
        return [
            'username.required' => 'username is required',
            'username.string' => 'username should be a string.',
            'username.max' => 'username should not be more than '.USERNAME_LENGTH.' characters.',
        ];
    }

}
