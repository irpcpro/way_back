<?php

namespace App\Http\Controllers\API\V1\SearchUser;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Facade\APIResponse;
use App\Http\Requests\SearchUser\SearchUserIDRequest;
use App\Http\Requests\SearchUser\SearchUserRequest;
use App\Http\Resources\V1\User\UserInfo;
use App\Http\Resources\V1\User\UserSearch;
use App\Models\User;

class SearchUserController extends Controller
{

    public function search(SearchUserRequest $request)
    {
        // get inputs
        $mobile = $request->validated('query');
        $current_user = auth()->user();

        // search user
        $find_user = User::query();
        $find_user = $find_user->where(function ($query) use ($mobile) {
            $query->where('mobile', 'like', "%$mobile%")
                ->orWhere('username', 'like', "%$mobile%")
                ->orWhere('name', 'like', "%$mobile%");
        });
        $find_user = $find_user->with('message_hook_member.message_hook.message_hook_members', function($query) use ($current_user){
            $query->where('id_user', $current_user->id_user);
        });
        $find_user = $find_user->whereNot('id_user',$current_user->id_user);
        // if nothing found
        if(!$find_user->exists())
            APIResponse('user not found.')->setData([])->send();
        $find_user = $find_user->get();
        $find_user = UserSearch::collection($find_user);
        $find_user = $find_user->toArray($request);

        // response the answer
        $response = APIResponse('user found.', 200, true);
        $response = $response->setData($find_user);

        $response->send();
    }

    public function searchID(SearchUserIDRequest $request){
        $userID = $request->validated('id');
        $getUser = User::query()->where('id_user', $userID);
        $getUser = $getUser->first();
        $getUser = new UserInfo($getUser);
        APIResponse('user found.')->setData($getUser->toArray($request))->send();
    }

}
