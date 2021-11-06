# Broadcasting

---

- The broadcast driver implementation ([BroadcastBroker][link-broadcast-broker]) will already be bound into the container by default.
- This driver is responsible for extracting private/presence channel names and dispatching the broadcast event that any action in messenger calls for.
    - If push notifications are enabled, this broker will also forward its data to the [PushNotificationService][link-push-notify]. The service will then fire a [PushNotificationEvent][link-push-event] that you can attach a listener to and handle your own FCM / other push service.
- ALL events broadcasted implement laravel's `ShouldBroadcastNow` interface, and will not be queued, but broadcasted immediately.

---

***Default push notifications config:***

```php
'push_notifications' => env('MESSENGER_PUSH_NOTIFICATIONS_ENABLED', false),
```

---

### Failed Broadcast

- When a broadcast fails and throws an exception, it will be caught and dispatched in the [BroadcastFailedEvent][link-broadcast-failed-event].
  - You must attach your own event listener if you would like to report the exception thrown and have access to the failed broadcast data.
  - Please see the [Events Documentation][link-events-docs] for more information.

---

## Channel Routes

```php
Broadcast::channel('messenger.call.{call}.thread.{thread}', CallChannel::class); // Presence
Broadcast::channel('messenger.thread.{thread}', ThreadChannel::class); // Presence
Broadcast::channel('messenger.{alias}.{id}', ProviderChannel::class); // Private
```

---

### View the [API Explorer][link-api-explorer] for a list of each broadcast and example payloads.

---

## Private Channel

***Events and their `broadcastAs` name***
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
FriendRemovedBroadcast::class => 'friend.removed',
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

***Laravel Echo private channel example:***

```js
Echo.private('messenger.user.1')
  .listen('.new.message', (e) => console.log(e))
  .listen('.thread.archived', (e) => console.log(e))
  .listen('.message.archived', (e) => console.log(e))
  .listen('.knock.knock', (e) => console.log(e))
  .listen('.new.thread', (e) => console.log(e))
  .listen('.thread.approval', (e) => console.log(e))
  .listen('.thread.left', (e) => console.log(e))
  .listen('.incoming.call', (e) => console.log(e))
  .listen('.joined.call', (e) => console.log(e))
  .listen('.ignored.call', (e) => console.log(e))
  .listen('.left.call', (e) => console.log(e))
  .listen('.call.ended', (e) => console.log(e))
  .listen('.friend.request', (e) => console.log(e))
  .listen('.friend.approved', (e) => console.log(e))
  .listen('.friend.cancelled', (e) => console.log(e))
  .listen('.friend.removed', (e) => console.log(e))
  .listen('.promoted.admin', (e) => console.log(e))
  .listen('.demoted.admin', (e) => console.log(e))
  .listen('.permissions.updated', (e) => console.log(e))
  .listen('.friend.denied', (e) => console.log(e))
  .listen('.call.kicked', (e) => console.log(e))
  .listen('.thread.read', (e) => console.log(e))
  .listen('.reaction.added', (e) => console.log(e))
  .listen('.reaction.removed', (e) => console.log(e))
```

- `ProviderChannel` Most data your client side will receive will be transmitted through this channel. To subscribe to this channel, follow the below example using the `alias` of the provider you set in your providers settings along with their `ID`:
    - `messenger.user.1` | User model with ID of `1`
    - `messenger.company.1234-5678` | Company model with ID of `1234-5678`

---

## Thread Presence Channel

***Events and their `broadcastAs` name***
```php
EmbedsRemovedBroadcast::class => 'embeds.removed',
MessageEditedBroadcast::class => 'message.edited',
ReactionAddedBroadcast::class => 'reaction.added',
ReactionRemovedBroadcast::class => 'reaction.removed',
ThreadAvatarBroadcast::class => 'thread.avatar',
ThreadSettingsBroadcast::class => 'thread.settings',
```

***Laravel Echo presence channel example:***

```js
//Presence
Echo.join('messenger.thread.1234-5678')
  .listen('.thread.settings', (e) => console.log(e))
  .listen('.thread.avatar', (e) => console.log(e))
  .listen('.message.edited', (e) => console.log(e))
  .listen('.reaction.added', (e) => console.log(e))
  .listen('.reaction.removed', (e) => console.log(e))
  .listen('.embeds.removed', (e) => console.log(e))
```

- `ThreadChannel` While inside a thread, you should subscribe to this presence channel. This is where realtime client to client events are broadcast. Typing, seen message, online status are all client to client and this is a great channel to utilize for this. The backend will broadcast a select few events over presence, such as when the groups settings are updated, or group avatar changed, or a user edited their message. This lets anyone currently in the thread know to update their UI! See example below for channel format to subscribe on:
    - `messenger.thread.1234-5678` | Thread presence channel for Thread model with ID of `1234-5678`

---

## Call Presence Channel

- `CallChannel` There are currently no broadcast from the backend to a call's presence channel. This channel exists for you to have a short-lived channel to connect to while in a call.
  - `messenger.call.4321.thread.1234-5678` | Call presence channel for Call model with ID of `1234` and Thread model with ID of `1234-5678`

***Laravel Echo presence channel example:***

```js
Echo.join('messenger.call.4321.thread.1234-5678').listen('.my.event', (e) => console.log(e))
```

---

## Interacting with the broadcaster
- It is very simple to reuse the included broadcast events, or to create your own, while being able to broadcast them yourself!
- The `BroadcastDriver` will be bound in the container, so you can call to it anytime using the helper, or dependency injection.

```php
use RTippin\Messenger\Contracts\BroadcastDriver;

//Using the container / dependency injection.
$broadcaster = app(BroadcastDriver::class);

//Using the helper.
$broadcaster = broadcaster();
```

#### Example broadcasting `NewMessageBroadcast` with custom data to a users private messenger channel
```php
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;

broadcaster()->to($receiver)->with([
    'body' => 'some data',
    'payload' => 'Any array data you want to send',
])->broadcast(NewMessageBroadcast::class);
```
```js
Echo.private('messenger.user.1').listen('.new.message', (e) => console.log(e))
```

### Creating and broadcasting custom events
- Your custom broadcast event will need to extend the abstract [MessengerBroadcast][link-messenger-broadcast] class.
- All you need to declare is the `broadcastAs` method. Everything else is set for you on demand!

#### Example custom broadcast event
```php
<?php

namespace App\Broadcasting;

class CustomBroadcast extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'custom.broadcast';
    }
}
```
```php
use App\Broadcasting\CustomBroadcast;

broadcaster()->to($receiver)->with([
    'message' => 'This is easy!',
])->broadcast(CustomBroadcast::class);
```
```js
Echo.private('messenger.user.1').listen('.custom.broadcast', (e) => console.log(e))
```

---

## Setting up your custom BroadcastDriver

- If you want to create your own broadcast driver implementation, your class must implement the [BroadcastDriver][link-broadcast-driver] interface.
- Once created, register your custom driver implementation in your `MessengerServiceProvider` boot method.

```php
<?php

namespace App\Providers;

use App\Brokers\CustomBroadcastBroker;
use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Facades\Messenger;

class MessengerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Messenger::setBroadcastDriver(CustomBroadcastBroker::class);
    }
}
```

[link-broadcast-broker]: https://github.com/RTippin/messenger/blob/1.x/src/Brokers/BroadcastBroker.php
[link-broadcast-driver]: https://github.com/RTippin/messenger/blob/1.x/src/Contracts/BroadcastDriver.php
[link-push-notify]: https://github.com/RTippin/messenger/blob/1.x/src/Services/PushNotificationService.php
[link-push-event]: https://github.com/RTippin/messenger/blob/1.x/src/Events/PushNotificationEvent.php
[link-messenger-broadcast]: https://github.com/RTippin/messenger/blob/1.x/src/Broadcasting/MessengerBroadcast.php
[link-api-explorer]: https://tippindev.com/api-explorer
[link-broadcast-failed-event]: https://github.com/RTippin/messenger/blob/1.x/src/Events/BroadcastFailedEvent.php
[link-events-docs]: Events.md