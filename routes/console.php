<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('webpush:vapid', function () {
    $keys = \App\Services\WebPush::generateVapidKeys();
    $this->line('VAPID_PUBLIC_KEY=' . $keys['public_key']);
    $this->line('VAPID_PRIVATE_KEY=' . $keys['private_key']);
})->purpose('Generate a VAPID key pair for web push notifications');
