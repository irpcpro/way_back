<?php

namespace App\Events;

use App\Http\Resources\V1\Message\MessageNewResource;
use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNewConversationsEvents implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $channelName;

    public function __construct(
        public User $user,
        public Message $message,
        public string|null $uuid
    )
    {
        $this->channelName = USER_CHANNEL_NAME;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel($this->channelName . $this->user->uuid);
    }

    public function broadcastWith()
    {
        return array_merge(
            (new MessageNewResource($this->message))->toArray(request()),
            ['uuid' => $this->uuid ?? null]
        );
    }

    public function broadcastAs(): string
    {
        return 'new_conversations';
    }

}
