<?php

/** @noinspection SpellCheckingInspection */

use RTippin\Messenger\Brokers\BroadcastBroker;
use RTippin\Messenger\Brokers\JanusBroker;
use RTippin\Messenger\Brokers\NullBroadcastBroker;
use RTippin\Messenger\Brokers\NullPushNotificationBroker;
use RTippin\Messenger\Brokers\NullVideoBroker;
use RTippin\Messenger\Brokers\PushNotificationBroker;

return [
    /*
    |--------------------------------------------------------------------------
    | The name of your application
    |--------------------------------------------------------------------------
    |
    */
    'site_name' => env('MESSENGER_SITE_NAME', 'Messenger'),

    /*
    |--------------------------------------------------------------------------
    | Provider UUIDs
    |--------------------------------------------------------------------------
    |
    | All of our tables that have relations to one of your providers will use
    | a morph. If your providers use UUIDs (char 36) as their primary keys,
    | then set this to true. Please note that if you use multiple providers,
    | they all must have matching primary key types (int / char).
    |
    */
    'provider_uuids' => false,

    /*
    |--------------------------------------------------------------------------
    | Included Queued Event Listeners
    |--------------------------------------------------------------------------
    |
    | This package includes several event listeners out of the box, to handle
    | system messages to setting up a call. All listeners are queued on the
    | 'messenger' channel, so you will need to setup a queue worker to monitor
    | that channel. You may also disable out listeners and setup your own! Feel
    | free to check ours out to see how we do things :)
    |
    */
    'queued_event_listeners' => true,

    /*
    |--------------------------------------------------------------------------
    | Messenger Providers Configuration
    |--------------------------------------------------------------------------
    |
    | List every model you wish to use within this messenger system
    | The name provided will be the alias used for that class for
    | everything including upload folder names, channel names, etc
    |
    | *PLEASE NOTE: Once you choose an alias, you should not change it
    | unless you plan to move the uploads/directory names around yourself
    |
    | *Each provider you list must implement our MessengerProvider contract
    | We also provide a Messagable trait you can use on your model that has
    | the basic methods this messenger needs (name / avatar / etc).
    | -RTippin\Messenger\Traits\Messageable
    | -RTippin\Messenger\Contracts\MessengerProvider
    |
    | *To enable a provider to be searchable, you must implement our contract
    | listed below, and implement the static method for query builder. We
    | include a trait you can use or reference to jump right in!
    | -RTippin\Messenger\Traits\Search
    | -RTippin\Messenger\Contracts\Searchable
    |
    | *Provider interactions give fine grain control over how your provider can interact with other providers, should you have
    | multiple. A provider always has full permission for interactions between itself, e.g : User to User. To allow full
    | interactions between other providers, simply mark each value as TRUE. For no other interactions, mark NULL or FALSE.
    | To specify which and how each provider can interact with one another, declare each providers alias string, multiple
    | separated by the PIPE, e.g : 'company', 'company|teacher|user', etc.
    |
    | 'company' => [                                  //alias given to your provider
    |     'model' => App\Models\Company::class,       //Path to the provider's model
    |     'searchable' => true,                       //Provider implements/is searchable - true|false
    |     'friendable' => true,                       //Provider is friendable - true|false
    |     'devices' => true,                          //Provider has tokens for push notifications - true|false
    |     'default_avatar' => public_path('pic.png')  //Default avatar path used when provider has no upload avatar
    |     'provider_interactions' => [                //What your provider can do with other providers
    |         'can_message' => 'user',                //Able to start new threads with other listed providers - true|false|null|string
    |         'can_search' => 'user|teacher',         //Able to search other listed providers - true|false|null|string
    |         'can_friend' => false,                  //Able to send friend request to  other listed providers - true|false|null|string
    |     ]
    | ],
    */
    'providers' => [
        'user' => [
            'model' => App\Models\User::class,
            'searchable' => true,
            'friendable' => true,
            'devices' => false,
            'default_avatar' => public_path('vendor/messenger/images/users.png'),
            'provider_interactions' => [
                'can_message' => true,
                'can_search' => true,
                'can_friend' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem settings for provider avatars and thread files
    |--------------------------------------------------------------------------
    |
    | For each option below, please seleck the filesystem disk and leading
    | directory you wish you use.
    |
    | *The avatar is where we will store a providers uploaded image. By default,
    | this will store into the storage_path('app/public/images/{alias}/{id}'),
    | given laravels default 'public' disk followed by 'images' directory
    |
    | *The threads option is where a thread avatar, image messages, and document
    | messages will be stored. You should not use the public directory as we
    | will process all files securely though the backend on request.
    |
    | //Thread files
    |
    | **Avatar - {disk}/{directory}/{threadID}/avatar
    | **Images - {disk}/{directory}/{threadID}/images
    | **Documents - {disk}/{directory}/{threadID}/documents
    |
    */
    'storage' => [
        'avatars' => [
            'disk' => 'public',
            'directory' => 'images',
        ],
        'threads' => [
            'disk' => 'messenger',
            'directory' => 'threads',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Messenger routing config
    |--------------------------------------------------------------------------
    |
    | You may choose to load our built in routes, or disable to setup your own.
    | The built in routing has all policy mappings and controllers already
    | setup. Our built in middleware 'messenger.provider' simply takes the
    | authenticated user via the request and sets them as the current
    | messenger provider. You are free to use your own custom middleware
    | to set your provider, and add any other middleware you may want,
    | such as 'auth:api' etc. We also include an extension of laravels
    | 'auth' middleware that lets it be optional for routes we want
    | both guest and auth to see, 'auth.optional|auth.optional:api'
    |
    | All API routes return json, and are best used stateless through
    | auth:api such as passport or sanctum.
    |
    | Invite view / redemption routes for both web and api have individual
    | middleware control so you may allow both guest or authed users to
    | access.
    |
    | *For the broadcasting channels to register, you must have already
    | setup/defined your laravel apps broadcast driver.
    |
    */
    'routing' => [
        'api' => [
            'enabled' => true,
            'domain' => null,
            'prefix' => 'api/messenger',
            'middleware' => [
                'web',
                'auth',
                'messenger.provider:required',
            ],
            'invite_api_middleware' => [
                'web',
                'auth.optional',
                'messenger.provider',
            ],
        ],
        'web' => [
            'enabled' => true,
            'domain' => null,
            'prefix' => 'messenger',
            'middleware' => [
                'web',
                'auth',
                'messenger.provider',
            ],
            'invite_web_middleware' => [
                'web',
                'auth.optional',
                'messenger.provider',
            ],
        ],
        'provider_avatar' => [
            'enabled' => true,
            'domain' => null,
            'prefix' => 'images',
            'middleware' => [
                'web',
                'cache.headers:public, max-age=86400;',
            ],
        ],
        'channels' => [
            'enabled' => true,
            'domain' => null,
            'prefix' => 'api',
            'middleware' => [
                'web',
                'auth',
                'messenger.provider:required',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoint our javascript will use for socket.io
    |--------------------------------------------------------------------------
    |
    */
    'socket_endpoint' => env('MESSENGER_SOCKET_ENDPOINT', config('app.url')),

    /*
    |--------------------------------------------------------------------------
    | Feature/Service drivers
    |--------------------------------------------------------------------------
    |
    | Below are the service drivers we use for features within this messenger
    | system. You are free to create your own service drivers as long as they
    | implement the corresponding contract. See each Drivers contract for
    | more information.
    |
    | * NULL drivers are a simple way to disable a service,
    |  especially useful in testing.
    |
    */
    'drivers' => [
        'broadcasting' => [
            'default' => BroadcastBroker::class,
            'null' => NullBroadcastBroker::class,
        ],
        'push_notifications' => [
            'default' => PushNotificationBroker::class,
            'null' => NullPushNotificationBroker::class,
        ],
        'calling' => [
            'janus' => JanusBroker::class,
            'null' => NullVideoBroker::class,
        ],
    ],

    'broadcasting' => [
        'driver' => env('MESSENGER_BROADCASTING_DRIVER', 'default'),
    ],

    'push_notifications' => [
        'driver' => env('MESSENGER_PUSH_NOTIFICATION_DRIVER', 'default'),
    ],

    'calling' => [
        'enabled' => env('MESSENGER_CALLING_ENABLED', false),
        'driver' => env('MESSENGER_CALLING_DRIVER', 'null'),
    ],

    /*
    |--------------------------------------------------------------------------
    | File toggles to enable / disable features and default image paths
    |--------------------------------------------------------------------------
    |
    | *Default thread avatars can be any file type, but the
    | array keys must be left as they are (1 - 5 .png)
    |
    */
    'files' => [
        'message_documents' => [
            'upload' => env('MESSENGER_MESSAGE_DOCUMENT_UPLOAD', true),
            'download' => env('MESSENGER_MESSAGE_DOCUMENT_DOWNLOAD', true),
        ],
        'message_images' => [
            'upload' => env('MESSENGER_MESSAGE_IMAGE_UPLOAD', true),
        ],
        'thread_avatars' => [
            'upload' => env('MESSENGER_THREAD_AVATAR_UPLOAD', true),
        ],
        'provider_avatars' => [
            'upload' => env('MESSENGER_PROVIDER_AVATAR_UPLOAD', true),
            'removal' => env('MESSENGER_PROVIDER_AVATAR_REMOVAL', true),
        ],
        'default_thread_avatars' => [
            '1.png' => public_path('vendor/messenger/images/1.png'),
            '2.png' => public_path('vendor/messenger/images/2.png'),
            '3.png' => public_path('vendor/messenger/images/3.png'),
            '4.png' => public_path('vendor/messenger/images/4.png'),
            '5.png' => public_path('vendor/messenger/images/5.png'),
        ],
        'default_not_found_image' => public_path('vendor/messenger/images/image404.png'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Thread invitations
    |--------------------------------------------------------------------------
    |
    | Enable or disable thread invites. You may also set the max active
    | invites each thread may have at any given time. 0 for unlimited
    |
    */
    'invites' => [
        'enabled' => env('MESSENGER_INVITES_ENABLED', true),
        'max_per_thread' => env('MESSENGER_INVITES_THREAD_MAX', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Knock knock!! 👊
    |--------------------------------------------------------------------------
    |
    | Enable or disable knocks, and set the timeout limit (in minutes)
    |
    */
    'knocks' => [
        'enabled' => env('MESSENGER_KNOCKS_ENABLED', true),
        'timeout' => env('MESSENGER_KNOCKS_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider online/away status
    |--------------------------------------------------------------------------
    |
    | Enable or disable showing online/away states, and set the lifetime the
    | status will live in cache (in minutes)
    |
    */
    'online_status' => [
        'enabled' => env('MESSENGER_ONLINE_STATUS_ENABLED', true),
        'lifetime' => env('MESSENGER_ONLINE_STATUS_LIFETIME', 4),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource collection results limit
    |--------------------------------------------------------------------------
    |
    | Here you can define the default query limits for resource collections
    |
    */
    'collections' => [
        'search' => [
            'page_count' => 25,
        ],
        'threads' => [
            'index_count' => 100,
            'page_count' => 25,
        ],
        'participants' => [
            'index_count' => 500,
            'page_count' => 50,
        ],
        'messages' => [
            'index_count' => 50,
            'page_count' => 50,
        ],
        'calls' => [
            'index_count' => 25,
            'page_count' => 25,
        ],
    ],
];
