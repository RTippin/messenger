<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Janus Server Configurations
    |--------------------------------------------------------------------------
    |
    */
    'server_endpoint' => env('JANUS_SERVER_ENDPOINT', 'http://janus:8088/janus'),
    'server_admin_endpoint' => env('JANUS_SERVER_ADMIN_ENDPOINT', 'http://janus:7088/admin'),
    'server_public_admin_endpoint' => env('JANUS_PUBLIC_SERVER_ADMIN_ENDPOINT', null),
    'backend_ssl' => env('JANUS_BACKEND_SSL', false),
    'log_failures' => env('JANUS_LOG_ERRORS', false),
    'backend_debug' => env('JANUS_BACKEND_DEBUG', false),
    'client_debug' => env('JANUS_CLIENT_DEBUG', false),
    'admin_secret' => env('JANUS_ADMIN_SECRET', null),
    'api_secret' => env('JANUS_API_SECRET', null),
    'video_room_secret' => env('JANUS_VIDEO_ROOM_SECRET', null),

    // Frontend servers / ice servers
    // This will set the config for our
    // janus server should you use it

    'main_servers' => [
        //"wss://example.com/janus-ws",
        //"https://example.com/janus",
    ],
    'ice_servers' => [
        //        [
        //            'urls' => 'stun:example.com:5349',
        //            'username' => 'user',
        //            'credential' => 'password'
        //        ],
    ],
];
