<?php

namespace App\Http\Controllers\API\V1\Push;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function publicKey()
    {
        return APIResponse('push public key.', 200, true)
            ->setData(['public_key' => config('services.webpush.public_key')])
            ->get();
    }

    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:500'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
        ]);

        // keyed by endpoint: if another user was logged in on this browser before, the device moves to the current user
        PushSubscription::updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'id_user' => auth()->user()->id_user,
                'p256dh' => $validated['keys']['p256dh'],
                'auth' => $validated['keys']['auth'],
            ]
        );

        return APIResponse('push subscribed.', 200, true)->get();
    }

    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        PushSubscription::where('endpoint', $validated['endpoint'])
            ->where('id_user', auth()->user()->id_user)
            ->delete();

        return APIResponse('push unsubscribed.', 200, true)->get();
    }
}
