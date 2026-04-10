<?php

namespace App\Http\Controllers\API\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UsersInfo\CheckUsernameRequest;
use App\Http\Requests\UsersInfo\UserInfoUpdateRequest;
use App\Http\Resources\V1\User\UserInfo;
use App\Models\User;
use Illuminate\Http\Request;

class UserInfoController extends Controller
{

    public function update(UserInfoUpdateRequest $request)
    {
        // get user
        $user = auth()->user();
        $user->update($request->validated());

        // get user info resources
        $user_details = new UserInfo($user);
        $user_details = $user_details->toArray($request);

        // response
        $response = APIResponse('username info updated.', 200, true);
        $response->setData($user_details);
        $response->send();
    }

    public function get(Request $request)
    {
        // get user
        $user = auth()->user();

        // get user info resources
        $user_details = new UserInfo($user);
        $user_details = $user_details->toArray($request);

        // response
        $response = APIResponse('data received.', 200, true);
        $response->setData($user_details);
        $response->send();
    }

    public function checkUsername(CheckUsernameRequest $request){
        $current_user_id = auth()->user()->id_user;
        $username_exists = User::where('username', $request->username)->where('id_user', '!=', $current_user_id)->exists();
        APIResponse('checked.', 200, true)->setData([
            'is_exists' => $username_exists,
            '$current_user_id' => $current_user_id
        ])->send();
    }

}
