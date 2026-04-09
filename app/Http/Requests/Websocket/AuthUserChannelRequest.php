<?php

namespace App\Http\Requests\Websocket;

use App\Http\Requests\AppRequest;

class AuthUserChannelRequest extends AppRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'socket_id' => ['required'],
            'channel_name' => ['required','string'],
        ];
    }

    public function messages()
    {
        return [
            'socket_id.required' => 'socket_id in required.',
            'channel_name.required' => 'channel_name in required.',
            'channel_name.string' => 'channel_name should be string.'
        ];
    }

}
