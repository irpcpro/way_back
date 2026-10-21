<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $primaryKey = 'id_push_subscription';

    protected $fillable = [
        'id_user',
        'endpoint',
        'p256dh',
        'auth',
    ];

    protected $hidden = [
        'p256dh',
        'auth',
    ];
}
