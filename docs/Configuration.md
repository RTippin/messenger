# Configuration

---

### Provider UUIDs

***Default:***

```php
'provider_uuids' => false,
```

#### SET THIS BEFORE MIGRATING

- All tables in this package that have relations to one of your `MessengerProvider` models will use polymorphic `morphTo` columns. If your providers use UUIDs (char 36) as their primary keys, then set this to true. 
- Please note that if you use multiple providers, they all must have matching primary key types (int / char / etc.).
- This also determines the primary key type on the internal `Bot` model and its related columns.

---

### Routing

***Default:***

```php
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
```
- `api` - The core routes of this package. They bootstrap all the policies and controllers!
- `assets` - Routes that deliver all files (images, avatars, documents, audio files, etc).
- `channels` - The broadcast channels utilized for realtime data! 
  - `MessengerProvider` private channel: `messenger.{alias}.{id}`
  - `Thread` presence channel: `messenger.thread.{thread}`. 
  - `Call` presence channel: `messenger.call.{call}.thread.{thread}`
- For each section of routes, you may choose your desired endpoint domain, prefix and middleware.
- The included `messenger.provider` middleware sets the active messenger provider by grabbing the authenticated user from `$request->user()`. 
  - See [SetMessengerProvider][link-set-provider-middleware] for more information.

***Example middleware using Sanctum***

```php
'routing' => [
    'api' => [
        'domain' => null,
        'prefix' => 'api/messenger',
        'middleware' => ['api', 'auth:sanctum', 'messenger.provider:required'],
        'invite_api_middleware' => ['api', 'messenger.provider'],
    ],
    'assets' => [
        'domain' => null,
        'prefix' => 'messenger/assets',
        'middleware' => ['api', 'cache.headers:public, max-age=86400;'],
    ],
    'channels' => [
        'enabled' => true,
        'domain' => null,
        'prefix' => 'api',
        'middleware' => ['api', 'auth:sanctum', 'messenger.provider:required'],
    ],
],
```

---

### Rate Limits

***Default:***

```php
'rate_limits' => [
    'api' => 1000,      // Applies over entire API
    'search' => 45,     // Applies on search
    'message' => 60,    // Applies to sending messages per thread
    'attachment' => 15, // Applies to uploading attachments per thread
],
```
- You can set the rate limits for the API, including fine grain control over search, messaging, and attachment uploads. 
  - Setting a limit to `0` will remove its rate limiter entirely.

---

### Storage

***Default:***

```php
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
```

- The default `disk` for both `avatars` and `threads` is `public`, laravel's default disk defined in the `filesystem.php` config file. You may choose any disk you have defined.
- `avatars` is where each provider's uploaded profile images are stored. By default, this will store into the following path, prefixed by the directory:
  - `storage_path('app/public/images/{alias}/{id}')`
- `threads` is where any uploads pertaining to a given thread are stored, such as images, documents, and audio files. By using the default config above, thread files will be stored in the following paths, prefixed by the directory:
  - Avatar - `storage_path('app/public/threads/{threadID}/avatar')`
  - Images - `storage_path('app/public/threads/{threadID}/images')`
  - Documents - `storage_path('app/public/threads/{threadID}/documents')`
  - Audio - `storage_path('app/public/threads/{threadID}/audio')`
  - Video - `storage_path('app/public/threads/{threadID}/videos')`
  - Bots - `storage_path('app/public/threads/{threadID}/bots/{botID}')`

---

### Files

***Default:***

```php
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
```

- Enable/disable the upload of message attachments.
- Enable/disable the upload of avatars for providers, threads, and bots.
- Set the upload max size limits, in kilobytes.
- Set allowed mime types on uploaded files, using the extension separated by a comma (following laravel's validation rule `mime:pdf,docx`).
- Set default images for a missing image, ghost profile, group thread avatar, and bot avatar.

---

### Calling

***Default:***

```php
'calling' => [
    'enabled' => env('MESSENGER_CALLING_ENABLED', false),
    'subscriber' => [
        'enabled' => true,
        'queued' => true,
        'channel' => 'messenger',
    ],
],
```

- Video calling is disabled by default. Please refer to the [Calling Documentation][link-calling-docs] for more detailed setup steps.

---

### System Messages

***Default:***

```php
'system_messages' => [
    'enabled' => env('MESSENGER_SYSTEM_MESSAGES_ENABLED', true),
    'subscriber' => [
        'enabled' => true,
        'queued' => true,
        'channel' => 'messenger',
    ],
],
```

- System messages are enabled by default. These are messages generated by actions to give feedback in the thread history. Actions such as: call ended, left group, promoted admin, etc.
- The included event subscriber ([SystemMessageSubscriber][link-system-message-subscriber]) will listen and react to events that generate the system messages. You may choose to enable it, whether it puts jobs on the queue or not, and which queue channel its jobs are dispatched on.

---

### Chat Bots

***Default:***

```php
'bots' => [
    'enabled' => env('MESSENGER_BOTS_ENABLED', false),
    'subscriber' => [
        'enabled' => true,
        'queued' => true,
        'channel' => 'messenger-bots',
    ],
],
```

- Bots are disabled by default. Please refer to the [Chat Bots Documentation][link-bots-docs] for more detailed setup steps.

---

### Message Size Limit

***Default:***

```php
'message_size_limit' => env('MESSENGER_MESSAGE_SIZE_LIMIT', 5000),
```

- Set the max character limit for sending messages (message body). This also applies when editing a message.

---

### Thread Verifications | Friendship Checks

***Default:***

```php
'thread_verifications' => [
    'private_thread_friendship' => env('MESSENGER_VERIFY_PRIVATE_THREAD_FRIENDSHIP', true),
    'group_thread_friendship' => env('MESSENGER_VERIFY_GROUP_THREAD_FRIENDSHIP', true),
],
```

- `private_thread_friendship` If enabled, the private thread will be marked as pending upon creation if the two participants are not friends. The recipient will then have the option to accept or deny the new private thread request.
- `group_thread_friendship` If enabled, only friends of the active participant may be added to the group thread, otherwise any valid messenger provider may be added as a participant.

---

### Editable Messages

***Default:***

```php
'message_edits' => [
    'enabled' => env('MESSENGER_MESSAGE_EDITS_ENABLED', true),
    'history_view' => env('MESSENGER_MESSAGE_EDITS_VIEW_HISTORY', true),
],
```

- Allow message owners to edit their messages, and for anyone in the thread to view the history of edits for a message.

---

### Message Reactions

***Default:***

```php
'message_reactions' => [
    'enabled' => env('MESSENGER_MESSAGE_REACTIONS_ENABLED', true),
    'max_unique' => env('MESSENGER_MESSAGE_REACTIONS_MAX_UNIQUE', 10),
],
```

- Allow message reactions on individual messages.
- Set the max unique allowed per message.
    - This feature behaves similar to discord, where a single user may react to a single message more than once with different emotes.

---

### Group Invites

***Default:***

```php
'invites' => [
    'enabled' => env('MESSENGER_INVITES_ENABLED', true),
    'max_per_thread' => env('MESSENGER_INVITES_THREAD_MAX', 3),
],
```

- Group invites allow users inside a group thread to create an invitation code / link. Anyone not already a participant of the group will be able to join automatically by using that link.
    - You may disable this feature, or even constrain how many active invites a group thread may have at any point in time.

---

### Knocks

***Default:***

```php
'knocks' => [
    'enabled' => env('MESSENGER_KNOCKS_ENABLED', true),
    'timeout' => env('MESSENGER_KNOCKS_TIMEOUT', 5),
],
```

- Knocks are a fun way to grab attention of others within a thread! Users can knock at one another in a private thread, where in a group thread, admins or participants with permission may use that feature.
    - You may disable this feature, or set the timeout a user can knock at a thread (in minutes). `0` for the timeout will be no timeout!

---

### Online status

***Default:***

```php
'online_status' => [
    'enabled' => env('MESSENGER_ONLINE_STATUS_ENABLED', true),
    'lifetime' => env('MESSENGER_ONLINE_STATUS_LIFETIME', 4),
],
```

- Online status will use a combination of the cache and database to show other users when you are online / away / offline.
    - You may disable this feature, or specify how long a users online status will live within the cache.

---

### Collections

***Default:***

```php
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
```

- JSON resources and collections are used to return content over our API. You may set the sizes of collection responses here.

[link-set-provider-middleware]: https://github.com/RTippin/messenger/blob/1.x/src/Http/Middleware/SetMessengerProvider.php
[link-system-message-subscriber]: https://github.com/RTippin/messenger/blob/1.x/src/Listeners/SystemMessageSubscriber.php
[link-calling-docs]: Calling.md
[link-bots-docs]: ChatBots.md