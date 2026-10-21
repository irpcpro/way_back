<?php

namespace App\Services;

use App\Enums\MessageTypeEnum;
use App\Models\Message;
use App\Models\MessageHookMembers;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MessagePushNotifier
{
    /**
     * Push a new message only to the other members of its message_hook (never the sender, never non-members).
     */
    public static function notifyNewMessage(Message $message): void
    {
        if (!config('services.webpush.public_key') || !config('services.webpush.private_key'))
            return;

        $recipientIds = MessageHookMembers::where('id_message_hook', $message->id_message_hook)
            ->where('id_user', '!=', $message->id_user)
            ->pluck('id_user');

        if ($recipientIds->isEmpty())
            return;

        $subscriptions = PushSubscription::whereIn('id_user', $recipientIds)->get();
        if ($subscriptions->isEmpty())
            return;

        $sender = $message->user;
        $body = $message->type == MessageTypeEnum::ATTACHMENT->value
            ? '📷 ' . ($message->context ? Str::limit($message->context, 100) : 'Photo')
            : Str::limit((string) $message->context, 120);

        $payload = [
            'title' => $sender?->name ?? 'New message',
            'body' => $body,
            'id_message_hook' => $message->id_message_hook,
            'url' => '/message/' . $message->id_message_hook,
            'tag' => 'message-hook-' . $message->id_message_hook,
        ];

        foreach ($subscriptions as $subscription) {
            try {
                WebPush::send($subscription, $payload);
            } catch (\Throwable $exception) {
                Log::warning('web push failed', ['id_push_subscription' => $subscription->id_push_subscription, 'error' => $exception->getMessage()]);
            }
        }
    }
}
