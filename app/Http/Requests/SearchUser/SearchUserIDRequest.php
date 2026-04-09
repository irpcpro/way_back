<?php

namespace App\Http\Requests\SearchUser;

use App\Http\Requests\AppRequest;
use App\Rules\MobileRule;
use App\Rules\UsernameRule;

class SearchUserIDRequest extends AppRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'exists:users,id_user'],
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'The id field is required.',
            'id.string' => 'The id field must be a string.',
            'id.exists' => 'The id field does not exist.',
        ];
    }

}
