<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Provider UUIDs
    |--------------------------------------------------------------------------
    |
    | All of our tables that have relations to one of your providers will use
    | a morphTo. If your providers use UUIDs (char 36) as their primary keys,
    | then set this to true. Please note that if you use multiple providers,
    | they all must have matching primary key types (int / char / etc).
    |
    */
    'provider_uuids' => false,

    /*
    |--------------------------------------------------------------------------
    | Filesystem settings for provider avatars and thread files
    |--------------------------------------------------------------------------
    |
    | For each option below, please select the filesystem disk and leading
    | directory you wish you use.
    |
    | *The "avatars" is where each provider's uploaded profile images are stored.
    | By default, this will store into the following path
    | prefixed by the directory:
    |
    | **Provider avatars - storage_path('app/public/images/{alias}/{id}')
    |
    | *The "threads" is where we store any uploads pertaining to a given thread,
    | such as images, documents, and audio files. By using the default config
    | below, thread files will be stored in the following paths prefixed by
    | the directory:
    |
    | **Avatar - storage_path('app/public/threads/{threadID}/avatar')
    | **Images - storage_path('app/public/threads/{threadID}/images')
    | **Documents - storage_path('app/public/threads/{threadID}/documents')
    | **Audio - storage_path('app/public/threads/{threadID}/audio')
    | **Video - storage_path('app/public/threads/{threadID}/videos')
    | **Bots - storage_path('app/public/threads/{threadID}/bots/{botID}')
    |
    */
    'storage' => [
        'avatars' => [
            'disk' => 'public',
            'directory' => 'images',
        ],
        'threads' => [
            'disk' => 'public',
            'directory' => 'threads',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Messenger routing config
    |--------------------------------------------------------------------------
    |
    | Our API is the core of this package, and bootstrap's all of our policies
    | and controllers for you. The included middleware 'messenger.provider'
    | simply takes the authenticated user via the request and sets them
    | as the current messenger provider. You are free to use your own
    | custom middleware to set your provider, as well as  any other
    | middleware you may want, such as 'auth:api' etc.
    |
    | All API routes return json, and are best used stateless through
    | auth:api such as passport or sanctum.
    |
    | Invite api has individual middleware control, giving fine-grain control
    | such as allowing both guest and authed users to access.
    |
    | *For the broadcasting channels to register, you must have already
    | setup/defined your laravel apps broadcast driver.
    |
    */
    'routing' => [
        'api' => [
            'domain' => null,
            'prefix' => 'api/messenger',
            'middleware' => ['web', 'auth', 'messenger.provider:required'],
            'invite_api_middleware' => ['web', 'messenger.provider'],
        ],
        'assets' => [
            'domain' => null,
            'prefix' => 'messenger/assets',
            'middleware' => ['web', 'cache.headers:public, max-age=86400;'],
        ],
        'channels' => [
            'enabled' => true,
            'domain' => null,
            'prefix' => 'api',
            'middleware' => ['web', 'auth', 'messenger.provider:required'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Use Absolute Route Paths
    |--------------------------------------------------------------------------
    |
    | Whether routes generated from messenger use the absolute or shortened path.
    |
    */
    'use_absolute_routes' => env('MESSENGER_USE_ABSOLUTE_ROUTES', false),

    /*
    |--------------------------------------------------------------------------
    | API rate limits / request per minute allowed. Use 0 for unlimited
    |--------------------------------------------------------------------------
    |
    */
    'rate_limits' => [
        'api' => 1000,      // Applies over entire API
        'search' => 45,     // Applies on search
        'message' => 60,    // Applies to sending messages per thread
        'attachment' => 15, // Applies to uploading images/documents per thread
    ],

    /*
    |--------------------------------------------------------------------------
    | Max allowed characters for Message body / Edit Message body.
    |--------------------------------------------------------------------------
    |
    */
    'message_size_limit' => env('MESSENGER_MESSAGE_SIZE_LIMIT', 5000),

    /*
    |--------------------------------------------------------------------------
    | File toggles to enable / disable features and default image paths.
    | Size limits are the max upload size in kilobytes.
    |--------------------------------------------------------------------------
    |
    */
    'files' => [
        'message_documents' => [
            'upload' => env('MESSENGER_MESSAGE_DOCUMENT_UPLOAD', true),
            'size_limit' => env('MESSENGER_MESSAGE_DOCUMENT_SIZE_LIMIT', 10240),
            'mime_types' => env('MESSENGER_MESSAGE_DOCUMENT_MIME_TYPES', 'csv,doc,docx,json,pdf,ppt,pptx,rar,rtf,txt,xls,xlsx,xml,zip,7z'),
        ],
        'message_images' => [
            'upload' => env('MESSENGER_MESSAGE_IMAGE_UPLOAD', true),
            'size_limit' => env('MESSENGER_MESSAGE_IMAGE_SIZE_LIMIT', 5120),
            'mime_types' => env('MESSENGER_MESSAGE_IMAGE_MIME_TYPES', 'jpg,jpeg,png,bmp,gif,webp'),
        ],
        'message_audio' => [
            'upload' => env('MESSENGER_MESSAGE_AUDIO_UPLOAD', true),
            'size_limit' => env('MESSENGER_MESSAGE_AUDIO_SIZE_LIMIT', 10240),
            'mime_types' => env('MESSENGER_MESSAGE_AUDIO_MIME_TYPES', 'aac,mp3,oga,ogg,wav,weba,webm'),
        ],
        'message_videos' => [
            'upload' => env('MESSENGER_MESSAGE_VIDEO_UPLOAD', true),
            'size_limit' => env('MESSENGER_MESSAGE_VIDEO_SIZE_LIMIT', 15360),
            'mime_types' => env('MESSENGER_MESSAGE_VIDEO_MIME_TYPES', 'avi,mp4,ogv,webm,3gp,3g2,wmv,mov'),
        ],
        'avatars' => [
            'providers' => env('MESSENGER_PROVIDER_AVATARS_ENABLED', true),
            'threads' => env('MESSENGER_THREAD_AVATARS_ENABLED', true),
            'bots' => env('MESSENGER_BOT_AVATARS_ENABLED', true),
            'size_limit' => env('MESSENGER_AVATARS_SIZE_LIMIT', 5120),
            'mime_types' => env('MESSENGER_AVATARS_MIME_TYPES', 'jpg,jpeg,png,bmp,gif,webp'),
        ],
        'default_not_found_image' => public_path('vendor/messenger/images/image404.png'),
        'default_ghost_avatar' => public_path('vendor/messenger/images/users.png'),
        'default_thread_avatar' => public_path('vendor/messenger/images/threads.png'),
        'default_bot_avatar' => public_path('vendor/messenger/images/bots.png'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Calling
    |--------------------------------------------------------------------------
    | Enable or disable the calling feature. If enabled, you must also declare
    | the driver we will use within a boot method from one of your service
    | providers. You may use our messenger facade to set the driver.
    |
    | Messenger::setVideoDriver(JanusBroker::class);
    |
    | We provide an event subscriber to listen and react to calling events. You
    | may choose to enable it, whether it puts jobs on the queue or not, and
    | which queue channel its jobs are dispatched on.
    */
    'calling' => [
        'enabled' => env('MESSENGER_CALLING_ENABLED', false),
        'subscriber' => [
            'enabled' => true,
            'queued' => true,
            'channel' => 'messenger',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | System Messages
    |--------------------------------------------------------------------------
    |
    | Enable or disable system messages. These are messages generated by actions
    | to give feedback in the thread history. Actions such as: call ended, left
    | group, promoted admin, etc.
    |
    | We provide an event subscriber to listen and react to events that will
    | generate the system messages. You may choose to enable it, whether it
    | puts jobs on the queue or not, and which queue channel its jobs are
    | dispatched on.
    */
    'system_messages' => [
        'enabled' => env('MESSENGER_SYSTEM_MESSAGES_ENABLED', true),
        'subscriber' => [
            'enabled' => true,
            'queued' => true,
            'channel' => 'messenger',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bots
    |--------------------------------------------------------------------------
    |
    | Enable or disable the bots feature. When enabled, bots may be created
    | within group threads. A bot may contain many actions with triggers
    | that will respond to a message.
    |
    | We provide an event subscriber to listen and react to events that may
    | trigger a bot response. You may choose to enable it, whether it puts
    | jobs on the queue or not, and which queue channel its jobs are
    | dispatched on.
    */
    'bots' => [
        'enabled' => env('MESSENGER_BOTS_ENABLED', false),
        'subscriber' => [
            'enabled' => true,
            'queued' => true,
            'channel' => 'messenger-bots',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Push Notification Events
    |--------------------------------------------------------------------------
    |
    | Enable or disable firing our push notification event for every broadcast
    | that is not sent over presence. This system only works if you are using
    | our default BroadcastBroker for our broadcast driver.
    */
    'push_notifications' => env('MESSENGER_PUSH_NOTIFICATIONS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Thread Verifications | Friendship Checks
    |--------------------------------------------------------------------------
    |
    | Enable or disable friendship checks for threads.
    |
    | If enabled for private threads, the thread will be marked as pending upon
    | creation if the two participants are not friends. The recipient will then
    | have the option to accept or deny the new private thread request.
    |
    | If enabled for group threads, only friends of the active participant may
    | be added to the group, otherwise any valid messenger provider may be
    | added as a participant.
    */
    'thread_verifications' => [
        'private_thread_friendship' => env('MESSENGER_VERIFY_PRIVATE_THREAD_FRIENDSHIP', true),
        'group_thread_friendship' => env('MESSENGER_VERIFY_GROUP_THREAD_FRIENDSHIP', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Edits
    |--------------------------------------------------------------------------
    |
    | Enable or disable the edit message feature. When enabled, the owner of a
    | message will be allowed to edit that message. A history of the edits will
    | be stored, should you enable our default queued_event_listeners. You may
    | also allow/deny users in a thread to view the edit history of the message.
    |
    */
    'message_edits' => [
        'enabled' => env('MESSENGER_MESSAGE_EDITS_ENABLED', true),
        'history_view' => env('MESSENGER_MESSAGE_EDITS_VIEW_HISTORY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Reactions
    |--------------------------------------------------------------------------
    |
    | Enable or disable the message reactions feature and the max unique allowed
    | per message. This feature behaves similar to discord, where a single user
    | may react to a single message more than once with different emotes.
    |
    */
    'message_reactions' => [
        'enabled' => env('MESSENGER_MESSAGE_REACTIONS_ENABLED', true),
        'max_unique' => env('MESSENGER_MESSAGE_REACTIONS_MAX_UNIQUE', 10),
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
    | Enable or disable knocks, and set the timeout limit (in minutes).
    | Set to 0 for no timeout.
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
