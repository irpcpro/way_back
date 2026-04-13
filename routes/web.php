<?php

use App\Events\SendToMembersMessageHookEvent;
use App\Events\UserNewConversationsEvents;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Ramsey\Uuid\Uuid;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
echo ':)';
//    event(new UserNewConversationsEvents(\App\Models\User::where('id_user', 1)->first()));
//    \App\Models\MessageSeen::create([
//        'id_message' => 40,
//        'id_user' => 1
//    ]);

//    \App\Models\MessageSeen::create([
//        'id_message' => 37,
//        'id_user' => 1
//    ]);

//    $f = storage_path("app/private/attachments/1776113540f92152.jpg");
//    if (!File::exists($f))
//        dd('no');
//    $context = File::get($f);
//
////    $f = \Illuminate\Support\Facades\Storage::publicUrl("app/private/attachments/1776113540f92152.jpg");
//    dd(base64_encode($context));

});
