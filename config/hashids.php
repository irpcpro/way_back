<?php

return [
    'default' => 'main',
    'connections' => [

        'main' => [
            'salt' => env('HASHIDS_SALT', 'my-super-secret-salt'),
            'length' => 50,
        ],

        'alternative' => [
            'salt' => 'your-salt-string',
            'length' => 'your-length-integer',
        ],
    ],
];
