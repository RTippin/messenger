# Laravel Messenger

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Tests][ico-test]][link-test]
[![StyleCI][ico-styleci]][link-styleci]
[![License][ico-license]][link-license]

---
![Preview](docs/images/image1.png?raw=true)
---

### Notice - Alpha Release
- Until the official v1 release, many breaking changes may be made to this package. I will document most changes in the [CHANGELOG](CHANGELOG.md).

---

### Prerequisites
- PHP >= 7.4 | 8.0
- Laravel >= 8.42
- laravel broadcast driver configured.
- `SubstituteBindings::class` route model binding enabled in your API / WEB middleware.

### Features
- Realtime messaging between multiple models, such as a User, Admin, and Teacher model.
- RESTful API, allowing you to make your own UI or connect to a mobile app.
- Support for morph maps on your provider models. See: [Morph Maps][link-morph-maps]
- Private and group threads.
- Permissions per participant within a group thread.
- Send image, document or audio messages.
- Message reactions, replies and edits.
- Group thread chat-bots.
- Friends system.
- Search system.
- Online status.
- Provider avatars, group thread avatars.
- Calling / Group Calling.
- Group thread invitation links (like discord).
- All actions are protected behind policies.
- Scheduled commands for automation cleanup and checks.
- Queued jobs fired from our event subscribers.
- Many features can be toggled within our config, or at runtime using our `Messenger` facade.
- Optional extra payload when sending messages to allow custom json to be stored with the message.

### Upcoming
- Route params for API results / better pagination.
- Resizing and saving images when uploaded instead of on the fly.
- Video message type.
- React / Vue frontend.
- Configurable friend driver.
- Language file support.

### Notes
- If our event subscribers are enabled, the default queue channel your worker must monitor is `messenger`. You may define custom queue channels yourself within our config.
- The default bot subscriber will push jobs onto the `messenger-bots` queue channel.
- Our included commands that queue a job also use the `messenger` queue channel.
- If you enable calling, we support an included [Janus Media Server][link-janus-server] driver, however you will still need to install the media server yourself.
- Read through our [`messenger.php`][link-config] config file before migrating!

---

# Addons / Demo

### Messenger Bots
- Bot functionality is built into the core of this `MESSENGER` package, but you are responsible for registering your own bot handlers.
- For a variety of ready-to-go bot action handlers, as well as documentation for bot configurations, please visit:
  - [Messenger Bots][link-messenger-bots]

### Messenger Faker Commands
- An addon package useful in dev environments to mock/seed realtime events and messages can be found here:
  - [Messenger Faker][link-messenger-faker]

### Messenger Web UI
- Addon package containing ready-made web routes and publishable views / assets, including default images.
  - [Messenger Web UI][link-messenger-ui]

### Messenger Demo
- You may view our demo laravel 8 source with this package installed, including a live demo: 
  - [Demo Source][link-demo-source]
  - [Live Demo][link-live-demo]
- Demo User model for how we integrate them with our contracts:
  - [User Model][link-demo-user]
- Demo console kernel utilizes our commands to track active calls, purge archived files, etc
  - [Console Kernel][link-demo-kernel]

---

# Installation

### Via Composer

``` bash
$ composer require rtippin/messenger
```

### Publish Config
```bash
$ php artisan messenger:publish
```
- If using janus video driver, you may publish the janus config:
```bash
$ php artisan vendor:publish --tag=messenger.janus.config
```

### Migrate
***Check out the published [`messenger.php`][link-config] config file in your config directory. You are going to want to first specify if you plan to use UUIDs on your provider models before running the migrations. (False by default)***
```php
'provider_uuids' => false,
```
- Once uuids are set (true/false), go ahead and migrate!
```bash
$ php artisan migrate
```

# Configuration

### Providers
- Add every provider model you wish to use within the providers array in our config.

**Example:**

```php
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
    'company' => [
        'model' => App\Models\Company::class,
        'searchable' => true,
        'friendable' => true,
        'devices' => false,
        'default_avatar' => public_path('vendor/messenger/images/company.png'),
        'provider_interactions' => [
            'can_message' => true,
            'can_search' => true,
            'can_friend' => true,
        ],
    ],
],
```
- Each provider you define will need to implement our [`MessengerProvider`][link-messenger-contract] contract. We include a [`Messageable`][link-messageable] trait you can use on your providers that will usually suffice for your needs.

***Example:***

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Traits\Messageable;

class User extends Authenticatable implements MessengerProvider
{
    use Messageable;
}
```

---

#### Providers name

- To grab your providers name, our default returns the 'name' column from your model, stripping tags and making words uppercase. You may overwrite the way the name on your model is returned using the below method.

***Example:***

```php
public function getProviderName(): string
{
    return strip_tags(ucwords($this->first." ".$this->last));
}
```

---

#### Providers avatar column

- When provider avatar upload/removal is enabled, we use the default `string/nullable` : `picture` column on that provider models table.
  - You may overwrite the column name on your model using the below method, should your column be named differently.

***Example:***

```php
public function getProviderAvatarColumn(): string
{
    return 'avatar';
}
```

---

#### Providers last active column

- When online status is enabled, we use the default `timestamp` : `updated_at` column on that provider models table. This is used to show when a provider was last active, and is the column we will update when you use the messenger status heartbeat. 
  - You may overwrite the column name on your model using the below method, should your column be named differently.

***Example:***

```php
    public function getProviderLastActiveColumn(): string
    {
        return 'last_active';
    }
```

---

### Searchable

- If you want a provider to be searchable, you must implement our [`Searchable`][link-searchable] contract on those providers. We also include a [`Search`][link-search] trait that works out of the box with the default laravel User model.
  
***Example:***

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Searchable;
use RTippin\Messenger\Traits\Messageable;
use RTippin\Messenger\Traits\Search;

class User extends Authenticatable implements MessengerProvider, Searchable
{
    use Messageable;
    use Search;
}
```

- If you have different columns used to search for your provider, you can skip using the default `Search` trait, and define the public static method yourself.
  - We inject the query builder, along with the original full string search term, and an array of the search term exploded via spaces and commas.

***Example:***

```php
public static function getProviderSearchableBuilder(Builder $query,
                                                    string $search,
                                                    array $searchItems): Builder
{
    return $query->where(function (Builder $query) use ($searchItems) {
        foreach ($searchItems as $item) {
            $query->orWhere('company_name', 'LIKE', "%{$item}%");
        }
    })->orWhere('company_email', '=', $search);
}
```

---

### Devices

- Devices are a helpful way for you to attach a listener onto our [PushNotificationEvent][link-push-event]. When any broadcast over a private channel occurs, we forward a stripped down list of all recipients/providers and their types/IDs, along with the original data broadcasted over websockets, and the event name.
  - To use this default event, you must be using our `default` broadcast driver, and have `push_notifications` enabled. How you use the data from our event to send push notifications (FCM etc) is up to you!
  
***EXAMPLE:***

`Knock at group thread : PushNotificationEvent::class dispatched`

```php
PushNotificationEvent::class => $recipients //Collection

[
  [
    'owner_type' => 'App\Models\User',
    'owner_id' => 1,
  ],
  [
    'owner_type' => 'App\Models\Company',
    'owner_id' => 22,
  ]
]
```

```php
PushNotificationEvent::class => $broadcastAs //String

'knock.knock'
```

```php
PushNotificationEvent::class => $data //Array

[
    'thread' => [
        'id' => '92a46441-930e-4492-b9ab-d40df4f0b9c1',
        'type' => 2,
        'type_verbose' => 'GROUP',
        'group' => true,
        'name' => 'Test Group',
        //etc
    ],
    'sender' => [],
]
```

---

### Provider Interactions

- Provider interactions give fine grain control over how your provider can interact with other providers, should you have multiple.
  - A provider must first have `friendable` and `searchable` enabled for those interaction permissions to take effect. If a user can search anyone, but your company provider has `searchable` disabled, no companies would be searched.
  - A provider will always have full interactions between itself, meaning a user will always be able to friend another user, if the user provider is friendable. Setting `can_friend` to false will simply deny a user to initiate a friend request with any other providers you have defined.
- Accepted values are `true`, `false`, `null`, `alias`, `alias1|alias2`
  - FALSE or NULL will indicate that the provider cannot perform those interactions to any other provider you defined.
  - TRUE indicates the provider can perform those interactions to any other provider you defined.
  - ALIAS, or multiple aliases separated by the PIPE `|` indicate specific providers the selected provider can interact with.
- Meanings:
  - `can_message` is permissions to initiate a private conversation with another provider. This does not stop or alter private threads already created, nor does it impact group threads. Initiating a private thread is defined as a provider starting a new private thread with another provider. A user may not be able to start a conversation with a company, but a company may be allowed to start the conversation with the user. Once a private thread is created, it is business as usual!
  - `can_search` is what other providers your provider will be allowed to search for. A user may not be allowed to search for companies, so any companies would not be included in their API response, however a company performing a search may be allowed to have users in their results.
  - `can_friend` is permission to initiate a friend request with another provider. Similar to `can_message`, this permission only occurs when one provider sends another a friend request. Cancelling / Accepting / Denying a friend request, or your list of actual friends is not impacted by this permission.

***Example:***

```php
'provider_interactions' => [
    'can_message' => true,
    'can_search' => true,
    'can_friend' => true,
],
'provider_interactions' => [
    'can_message' => 'company|admin',
    'can_search' => 'company',
    'can_friend' => false,
],
'provider_interactions' => [
    'can_message' => null,
    'can_search' => 'company|admin|employee',
    'can_friend' => 'company',
],
```

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
        'disk' => 'messenger',
        'directory' => 'threads',
    ],
],
```

- The default path used for avatar uploads from your providers is set to the default `public` disk laravel uses in the `filesystem.php` config file. Images would then be saved under `storage_path('app/public/images')`
- The default path used for any uploads belonging to a thread is set to the `messenger` disk, which you will have to create within your `filesystem.php` config, or set to a disk of your choosing. Using the below example, thread files would be located under `storage_path('app/messenger/threads')`

***Example disk in filesystem.php:***
```php
'messenger' => [
    'driver' => 'local',
    'root' => storage_path('app/messenger'),
    'url' => env('APP_URL').'/storage',
],
```

---

### Routing

***Default:***

```php
'routing' => [
    'api' => [
        'domain' => null,
        'prefix' => 'api/messenger',
        'middleware' => ['web', 'auth', 'messenger.provider:required'],
        'invite_api_middleware' => ['web', 'auth.optional', 'messenger.provider'],
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
- Our API is the core of this package. The api routes bootstrap all of our policies and controllers for you!
- Asset routes deliver all files (images, avatars, documents, audio files, etc).
- Channels are what we broadcast our realtime data over! The included private channel: `private-messenger.{alias}.{id}`. Thread presence channel: `presence-messenger.thread.{thread}`. Call presence channel: `presence-messenger.call.{call}.thread.{thread}`
- For each section of routes, you may choose your desired endpoint domain, prefix and middleware.
- The default `messenger.provider` middleware is included with this package and simply sets the active messenger provider by grabbing the authed user from `$request->user()`. See [SetMessengerProvider][link-set-provider-middleware] for more information.

---

### Rate Limits

***Default:***

```php
'rate_limits' => [
    'api' => 1000,      // Applies over entire API
    'search' => 45,     // Applies on search
    'message' => 60,    // Applies to sending messages per thread
    'attachment' => 15, // Applies to uploading images/documents per thread
],
```
- You can set the rate limits for our API, including fine grain control over search, messaging, and attachment uploads. Setting a limit to `0` will remove its rate limiter entirely.

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

- Video calling is disabled by default. If enabled, you must set the driver within your own AppServiceProvider.
  - Our included 3rd party video driver ([JanusBroker][link-janus-broker]) uses an open source media server, [Janus Media Server][link-janus-server]. We only utilize their [VideoRoom Plugin][link-janus-video], using create and destroy room methods.
  - More video drivers will be included in the future, such as one for Twillio.
  - You can create your own video driver as well, implementing our contract [VideoDriver][link-video-driver]
- We provide an event subscriber ([CallSubscriber][link-call-subscriber]) to listen and react to calling events. You may choose to enable it, whether it puts jobs on the queue or not, and which queue channel its jobs are dispatched on.

***Set the video driver in your AppServiceProvider boot method:***

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Brokers\JanusBroker;
use RTippin\Messenger\Facades\Messenger;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Messenger::setVideoDriver(JanusBroker::class);
    }
}
```

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
- We provide an event subscriber ([SystemMessageSubscriber][link-system-message-subscriber]) to listen and react to events that will generate the system messages. You may choose to enable it, whether it puts jobs on the queue or not, and which queue channel its jobs are dispatched on.

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

- Bots are disabled by default. When enabled, bots may be created within group threads. A bot may contain many actions with triggers that will respond to a message.
- We provide an event subscriber ([BotSubscriber][link-bot-subscriber]) to listen and react to events that may trigger a bot response. You may choose to enable it, whether it puts jobs on the queue or not, and which queue channel its jobs are dispatched on.
  - For more information on setting up bot handlers, please visit [Messenger Bots][link-messenger-bots]

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
  - Message edit history will be stored in the `message_edits` table.

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
  - Message reactions will be stored in the `message_reactions` table.

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
  - You may disable this feature, or even constrain how many active invites a group thread may have at any one point in time.

---

### Knocks

***Default:***

```php
'knocks' => [
    'enabled' => env('MESSENGER_KNOCKS_ENABLED', true),
    'timeout' => env('MESSENGER_KNOCKS_TIMEOUT', 5),
],
```

- Knocks are a fun way to grab attention of others within a private or group thread! Users can knock at one another in a private thread, where in a group thread, admins or participants with permission may use that feature.
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

- We use JSON resources and collections to return content over our API. You can set how big you want the collections to be here.

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

- Enable/disable the upload message attachments.
- Enable/disable the upload and removal of avatars for providers, threads, and bots.
- Set upload max size limits, in kilobytes.
- Set allowed mime types on uploaded files, using the extension separated by a comma (following laravels validation rule `mime:pdf,docx`).
- Set a different default image to serve for a group thread, and the image used when another image is not found.

---

# Commands

- `php artisan messenger:publish` | `--force`
    * Publish our config files.
    * `--force` flag will overwrite existing files.
- `php artisan messenger:calls:check-activity` | `--now`
    * Check active calls for active participants, end calls with none.
    * `--now` flag to run immediately without dispatching jobs to queue.
- `php artisan messenger:calls:down` | `--duration=30` | `--now`
    * End all active calls and disable the calling system for the specified minutes (30 default).
    * `--duration=X` flag to set timeframe in minutes for calling to be disabled.
    * `--now` flag to run immediately without dispatching jobs to queue.
- `php artisan messenger:calls:up`
    * Put the call system back online if it is temporarily disabled.
- `php artisan messenger:invites:check-valid` | `--now`
    * Check active invites for any past expiration or max use cases and invalidate them.
    * `--now` flag to run immediately without dispatching jobs to queue.
- `php artisan messenger:providers:cache`
    * Cache the computed provider configs for messenger.
- `php artisan messenger:providers:clear`
    * Clear the cached provider config file.
- `php artisan messenger:purge:documents` | `--now` | `--days=30`
    * We will purge all soft deleted document messages that were archived past the set days (30 default). We run it through our action to remove the document file from storage and message from the database.
    * `--days=X` flag to set how many days in the past to start at.
    * `--now` flag to run immediately without dispatching jobs to queue.
- `php artisan messenger:purge:audio` | `--now` | `--days=30`
    * We will purge all soft deleted audio messages that were archived past the set days (30 default). We run it through our action to remove the audio file from storage and message from the database.
    * `--days=X` flag to set how many days in the past to start at.
    * `--now` flag to run immediately without dispatching jobs to queue.
- `php artisan messenger:purge:images` | `--now` | `--days=30`
    * We will purge all soft deleted image messages that were archived past the set days (30 default). We run it through our action to remove the image from storage and message from the database.
    * `--days=X` flag to set how many days in the past to start at.
    * `--now` flag to run immediately without dispatching jobs to queue.
- `php artisan messenger:purge:messages` | `--days=30`
    * We will purge all soft deleted messages that were archived past the set days (30 default). We do not need to fire any additional events or load models into memory, just remove from the table, as this is not messages that are documents or images. 
    * `--days=X` flag to set how many days in the past to start at.
- `php artisan messenger:purge:threads` | `--now` | `--days=30`
    * We will purge all soft deleted threads that were archived past the set days (30 default). We run it through our action to remove the entire thread directory and sub files from storage and the thread from the database.
    * `--days=X` flag to set how many days in the past to start at.
    * `--now` flag to run immediately without dispatching jobs to queue.
- `php artisan messenger:purge:bots` | `--now` | `--days=30`
    * We will purge all soft deleted bots that were archived past the set days (30 default). We run it through our action to remove the entire bot directory and sub files from storage and the bot from the database.
    * `--days=X` flag to set how many days in the past to start at.
    * `--now` flag to run immediately without dispatching jobs to queue.

---

# Broadcasting

- Our default broadcast driver ([BroadcastBroker][link-broadcast-broker]) is responsible for extracting private/presence channel names and dispatching the broadcast event that any action in our system calls for.
  - If push notifications are enabled, this broker will also forward its data to our [PushNotificationService][link-push-notify]. The service will then fire a [PushNotificationEvent][link-push-event] that you can attach a listener to handle your own FCM / other service.
- If using your own broadcast driver, your class must implement our [BroadcastDriver][link-broadcast-driver] contract. You may then declare your driver within your AppServiceProvider.

***Set the broadcast driver in your AppServiceProvider boot method:***
```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Brokers\NullBroadcastBroker;
use RTippin\Messenger\Facades\Messenger;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Messenger::setBroadcastDriver(CustomBroadcastBroker::class);
    }
}
```

***Default push notifications:***

```php
'push_notifications' => env('MESSENGER_PUSH_NOTIFICATIONS_ENABLED', false),
```

***Default Channel Routes:***

```php
$broadcaster->channel('messenger.call.{call}.thread.{thread}', CallChannel::class); // Presence
$broadcaster->channel('messenger.thread.{thread}', ThreadChannel::class); // Presence
$broadcaster->channel('messenger.{alias}.{id}', ProviderChannel::class); // Private
```
  
***Private Channel Broadcast:***

```php
CallEndedBroadcast::class => 'call.ended',
CallIgnoredBroadcast::class => 'call.ignored',
CallJoinedBroadcast::class => 'joined.call',
CallLeftBroadcast::class => 'left.call',
CallStartedBroadcast::class => 'incoming.call',
DemotedAdminBroadcast::class => 'demoted.admin',
FriendApprovedBroadcast::class => 'friend.approved',
FriendCancelledBroadcast::class => 'friend.cancelled',
FriendDeniedBroadcast::class => 'friend.denied',
FriendRequestBroadcast::class => 'friend.request',
KickedFromCallBroadcast::class => 'call.kicked',
KnockBroadcast::class => 'knock.knock',
MessageArchivedBroadcast::class => 'message.archived',
NewMessageBroadcast::class => 'new.message',
NewThreadBroadcast::class => 'new.thread',
ParticipantPermissionsBroadcast::class => 'permissions.updated',
ParticipantReadBroadcast::class => 'thread.read',
PromotedAdminBroadcast::class => 'promoted.admin',
ReactionAddedBroadcast::class => 'reaction.added',
ReactionRemovedBroadcast::class => 'reaction.removed',
ThreadApprovalBroadcast::class => 'thread.approval',
ThreadArchivedBroadcast::class => 'thread.archived',
ThreadLeftBroadcast::class => 'thread.left',
```

***JS Echo Example:***

```js
//Private
Echo.private('messenger.user.1')
  .listen('.new.message', incomingMessage)
  .listen('.thread.archived', threadLeft)
  .listen('.message.archived', messagePurged)
  .listen('.knock.knock', incomingKnok)
  .listen('.new.thread', newThread)
  .listen('.thread.approval', threadApproval)
  .listen('.thread.left', threadLeft)
  .listen('.incoming.call', incomingCall)
  .listen('.joined.call', callJoined)
  .listen('.ignored.call', callIgnored)
  .listen('.left.call', callLeft)
  .listen('.call.ended', callEnded)
  .listen('.friend.request', friendRequest)
  .listen('.friend.approved', friendApproved)
  .listen('.friend.cancelled', friendCancelled)
  .listen('.promoted.admin', promotedAdmin)
  .listen('.demoted.admin', demotedAdmin)
  .listen('.permissions.updated', permissionsUpdated)
  .listen('.friend.denied', friendDenied)
  .listen('.call.kicked', callKicked)
  .listen('.thread.read', threadRead)
  .listen('.reaction.added', reactionAdded)
  .listen('.reaction.removed', reactionRemoved)
```

- Most data your client side will receive will be done through the user/providers private channel. Broadcast such as messages, calls, friend request, knocks, and more will be transmitted over the `ProviderChannel`. To subscribe to this channel, follow the below example using the `alias` of the provider you set in your providers config:
  - `private-messenger.user.1` | User model with ID of 1
  - `private-messenger.company.1234-5678` | Company model with ID of 1234-5678

***Presence Channel Broadcast:***

```php
EmbedsRemovedBroadcast::class => 'embeds.removed',
MessageEditedBroadcast::class => 'message.edited',
ReactionAddedBroadcast::class => 'reaction.added',
ReactionRemovedBroadcast::class => 'reaction.removed',
ThreadAvatarBroadcast::class => 'thread.avatar',
ThreadSettingsBroadcast::class => 'thread.settings',
```

***JS Echo Example:***

```js
//Presence
Echo.join('messenger.thread.1234-5678')
  .listen('.thread.settings', groupSettingsState)
  .listen('.thread.avatar', groupAvatarState)
  .listen('.message.edited', renderUpdatedMessage)
  .listen('.reaction.added', reactionAdded)
  .listen('.reaction.removed', reactionRemoved)
  .listen('.embeds.removed', embedsRemoved)
```

- While inside a thread, you will want to subscribe to the `ThreadChannel` presence channel. This is where realtime, client to client events are broadcast. Typing, seen message, online status are all client to client and this is a great channel to utilize for this. The backend will broadcast a select few events over presence, such as when the groups settings are updated, or group avatar changed, or a user edited their message. This lets anyone currently in the thread know to update their UI! See example below for channel format to subscribe on:
  - `presence-messenger.thread.1234-5678` | Thread presence channel for Thread model with ID of 1234-5678

---

## API endpoints / examples

- [Threads][link-threads]
- [Participants][link-participants]
- [Messages][link-messages]
- [Bots][link-bots]
- [Document Messages][link-documents]
- [Image Messages][link-images]
- [Audio Messages][link-audio]
- [Message Reactions][link-reactions]
- [Messenger][link-messenger]
- [Friends][link-friends]
- [Calls][link-calls]
- [Invites][link-invites]

## Credits - [Richard Tippin][link-author]

## License - MIT

### Please see the [license file](LICENSE.md) for more information.

## Change log

Please see the [changelog](CHANGELOG.md) for more information on what has changed recently.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

[ico-version]: https://img.shields.io/packagist/v/rtippin/messenger.svg?style=plastic&cacheSeconds=3600
[ico-downloads]: https://img.shields.io/packagist/dt/rtippin/messenger.svg?style=plastic&cacheSeconds=3600
[ico-styleci]: https://styleci.io/repos/309521487/shield?style=plastic&cacheSeconds=3600
[ico-license]: https://img.shields.io/github/license/RTippin/messenger?style=plastic
[link-packagist]: https://packagist.org/packages/rtippin/messenger
[link-test]: https://github.com/RTippin/messenger/actions
[ico-test]: https://img.shields.io/github/workflow/status/rtippin/messenger/tests?style=plastic
[link-downloads]: https://packagist.org/packages/rtippin/messenger
[link-license]: https://packagist.org/packages/rtippin/messenger
[link-styleci]: https://styleci.io/repos/309521487
[link-author]: https://github.com/rtippin
[link-config]: config/messenger.php
[link-messageable]: src/Traits/Messageable.php
[link-searchable]: src/Contracts/Searchable.php
[link-search]: src/Traits/Search.php
[link-messenger-contract]: src/Contracts/MessengerProvider.php
[link-calls]: docs/Calls.md
[link-documents]: docs/Documents.md
[link-friends]: docs/Friends.md
[link-images]: docs/Images.md
[link-audio]: docs/Audio.md
[link-reactions]: docs/MessageReactions.md
[link-messages]: docs/Messages.md
[link-bots]: docs/Bots.md
[link-messenger]: docs/Messenger.md
[link-participants]: docs/Participants.md
[link-threads]: docs/Threads.md
[link-invites]: docs/Invites.md
[link-demo-source]: https://github.com/RTippin/messenger-demo
[link-live-demo]: https://tippindev.com
[link-demo-user]: https://github.com/RTippin/messenger-demo/blob/master/app/Models/User.php
[link-demo-kernel]: https://github.com/RTippin/messenger-demo/blob/master/app/Console/Kernel.php
[link-janus-server]: https://janus.conf.meetecho.com/docs/
[link-janus-video]: https://janus.conf.meetecho.com/docs/videoroom.html
[link-set-provider-middleware]: src/Http/Middleware/SetMessengerProvider.php
[link-listeners]: src/Listeners
[link-broadcast-broker]: src/Brokers/BroadcastBroker.php
[link-broadcast-driver]: src/Contracts/BroadcastDriver.php
[link-push-notify]: src/Services/PushNotificationService.php
[link-push-event]: src/Events/PushNotificationEvent.php
[link-messenger-faker]: https://github.com/RTippin/messenger-faker
[link-messenger-ui]: https://github.com/RTippin/messenger-ui
[link-morph-maps]: https://laravel.com/docs/8.x/eloquent-relationships#custom-polymorphic-types
[link-video-driver]: src/Contracts/VideoDriver.php
[link-janus-broker]: src/Brokers/JanusBroker.php
[link-call-subscriber]: src/Listeners/CallSubscriber.php
[link-system-message-subscriber]: src/Listeners/SystemMessageSubscriber.php
[link-bot-subscriber]: src/Listeners/BotSubscriber.php
[link-messenger-bots]: https://github.com/RTippin/messenger-bots