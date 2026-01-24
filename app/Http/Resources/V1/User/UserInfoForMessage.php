<?php

namespace App\Http\Resources\V1\User;

use App\Http\Resources\V1\Avatar\AvatarResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use function auth;

class UserInfoForMessage extends JsonResource
{

    public bool $user_is_current_user = false;
    public null|string $request_mobile = null;

    public function __construct(User $resource, null|string $request_mobile = null)
    {
        parent::__construct($resource);
        $this->current_user = auth()->user();
        if($this->resource->id_user == $this->current_user->id_user)
            $this->user_is_current_user = true;

        $this->request_mobile = $request_mobile;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_user' => $this->id_user,
            'avatar' => new AvatarResource($this->avatar),
        ];
    }
}
