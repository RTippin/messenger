# Broadcasting

---

- Our broadcast driver implementation ([BroadcastBroker][link-broadcast-broker]) will already be set by default.
- This driver is responsible for extracting private/presence channel names and dispatching the broadcast event that any action in our system calls for.
    - If push notifications are enabled, this broker will also forward its data to our [PushNotificationService][link-push-notify]. The service will then fire a [PushNotificationEvent][link-push-event] that you can attach a listener to handle your own FCM / other service.
- If using your own broadcast driver, your class must implement our [BroadcastDriver][link-broadcast-driver] contract. You may then declare your driver within your MessengerServiceProvider (or any service providers boot method).
- ALL events we broadcast implement laravel's `ShouldBroadcastNow` interface, being broadcast immediately and not queued.

***To overwrite our broadcast driver, set your custom driver in your `MessengerServiceProvider` boot method:***

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

***Default push notifications:***

```php
'push_notifications' => env('MESSENGER_PUSH_NOTIFICATIONS_ENABLED', false),
```

---

## Channel Routes

```php
$broadcaster->channel('messenger.call.{call}.thread.{thread}', CallChannel::class); // Presence
$broadcaster->channel('messenger.thread.{thread}', ThreadChannel::class); // Presence
$broadcaster->channel('messenger.{alias}.{id}', ProviderChannel::class); // Private
```

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
    - `private-messenger.user.1` | User model with ID of `1`
    - `private-messenger.company.1234-5678` | Company model with ID of `1234-5678`

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
    - `presence-messenger.thread.1234-5678` | Thread presence channel for Thread model with ID of `1234-5678`

---

## Call Presence Channel

- There are currently no broadcast from the backend to a call's presence channel. This channel exists for you to have a short-lived channel to connect to while in a call.
  - `presence-messenger.call.4321.thread.1234-5678` | Call presence channel for Call model with ID of `1234` and Thread model with ID of `1234-5678`

```js
//Presence
Echo.join('messenger.call.4321.thread.1234-5678')
  .listen('.my.event', handleMyEvent)
```

[link-broadcast-broker]: ../src/Brokers/BroadcastBroker.php
[link-broadcast-driver]: ../src/Contracts/BroadcastDriver.php
[link-push-notify]: ../src/Services/PushNotificationService.php
[link-push-event]: ../src/Events/PushNotificationEvent.php