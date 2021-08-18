# Messenger Composer

---

### This is a support class that allows you to easily send many of our core actions, such as messages, reactions, images, and more.

```php
use RTippin\Messenger\Facades\MessengerComposer;

MessengerComposer::to($receiver)
    ->from($sender)
    ->emitTyping()
    ->message('Hello!');
```

---

### General Flow
- Set the thread or messenger provider we want to compose `TO`.
- Set the `FROM` messenger provider who is composing.
- Decide if you want to silence the action or emit realtime broadcast.
- Call the action, such as `message()` to complete the cycle. 
  - The actions instance will be returned should you need to access it.

---

### `to($entity)`
- Set the `Thread` or `MessengerProvider` we want to compose `TO`. If a provider is supplied, we will attempt to locate a private thread between the `TO` and `FROM` providers. If no private thread is found, one will be created.
  - Please note that when a private thread is created through this composer, friend status between the two providers is ignored, and the thread will not be marked pending.

### `from(MessengerProvider $provider)`
- Set the provider who is composing. (acting as the logged-in user)

### `silent()`
- When sending our composed payload, silence any broadcast events.

```php
use App\Models\User;
use Illuminate\Http\Request;
use RTippin\Messenger\Facades\MessengerComposer;
use RTippin\Messenger\Models\Thread;

public function testing(): void
{
    MessengerComposer::to(Thread::first())
      ->from(User::first())
      ->message('I store and broadcast a new message to the given thread from the provided user!');
    
    MessengerComposer::to(User::latest()->first())
      ->from(User::oldest()->first())
      ->silent()
      ->message('I store a new message in a private thread between our two users without broadcasting it!');
}
```

---

### `message(?string $message, ?string $replyingToId = null, ?array $extra = null)`
- Send a message. Optional reply to message ID and extra data allowed.

```php
use Illuminate\Http\Request;
use RTippin\Messenger\Facades\MessengerComposer;
use RTippin\Messenger\Http\Resources\MessageResource;
use RTippin\Messenger\Models\Thread;

public function testing(Request $request, Thread $thread): MessageResource
{
    //Send inline directly.
    MessengerComposer::to($thread)
      ->from($request->user())
      ->message('Testing hello!');
      
    //Send and return the message actions json resource.
    return MessengerComposer::to($thread)
      ->from($request->user())
      ->message($request->input('message'))
      ->getJsonResource();
}
```

---

### `image(UploadedFile $image, ?string $replyingToId = null, ?array $extra = null)`
- Send an image message. Optional reply to message ID and extra data allowed.

```php
use Illuminate\Http\Request;
use RTippin\Messenger\Facades\MessengerComposer;
use RTippin\Messenger\Http\Resources\MessageResource;
use RTippin\Messenger\Models\Thread;

public function testing(Request $request, Thread $thread): MessageResource
{
    //Send inline directly.
    MessengerComposer::to($thread)
      ->from($request->user())
      ->image($request->file('image'));
      
    //Send and return the message actions json resource.
    return MessengerComposer::to($thread)
      ->from($request->user())
      ->image($request->file('image'))
      ->getJsonResource();
}
```

---

### `document(UploadedFile $document, ?string $replyingToId = null, ?array $extra = null)`
- Send a document message. Optional reply to message ID and extra data allowed.

```php
use Illuminate\Http\Request;
use RTippin\Messenger\Facades\MessengerComposer;
use RTippin\Messenger\Http\Resources\MessageResource;
use RTippin\Messenger\Models\Thread;

public function testing(Request $request, Thread $thread): MessageResource
{
    //Send inline directly.
    MessengerComposer::to($thread)
      ->from($request->user())
      ->document($request->file('document'));
      
    //Send and return the message actions json resource.
    return MessengerComposer::to($thread)
      ->from($request->user())
      ->document($request->file('document'))
      ->getJsonResource();
}
```

---

### `audio(UploadedFile $audio, ?string $replyingToId = null, ?array $extra = null)`
- Send an audio message. Optional reply to message ID and extra data allowed.

```php
use Illuminate\Http\Request;
use RTippin\Messenger\Facades\MessengerComposer;
use RTippin\Messenger\Http\Resources\MessageResource;
use RTippin\Messenger\Models\Thread;

public function testing(Request $request, Thread $thread): MessageResource
{
    //Send inline directly.
    MessengerComposer::to($thread)
      ->from($request->user())
      ->audio($request->file('song'));
      
    //Send and return the message actions json resource.
    return MessengerComposer::to($thread)
      ->from($request->user())
      ->audio($request->file('song'))
      ->getJsonResource();
}
```

---

### `reaction(Message $message, string $reaction)`
- Add a reaction to the supplied message.

```php
use Illuminate\Http\Request;
use RTippin\Messenger\Facades\MessengerComposer;
use RTippin\Messenger\Http\Resources\MessageReactionResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;

public function testing(Request $request, Thread $thread, Message $message): MessageReactionResource
{
    //Send inline directly.
    MessengerComposer::to($thread)
      ->from($request->user())
      ->reaction($message, '💯');
      
    //Send and return the message actions json resource.
    return MessengerComposer::to($thread)
      ->from($request->user())
      ->reaction($message, $request->input('reaction'))
      ->getJsonResource();
}
```

---

### `knock()`
- Send a knock to the given thread.

```php
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RTippin\Messenger\Facades\MessengerComposer;
use RTippin\Messenger\Models\Thread;

public function testing(Request $request, Thread $thread): JsonResponse
{
    MessengerComposer::to($thread)->from($request->user())->knock();

    return new JsonResponse([
        'message' => 'success'
    ]);
}
```

---

### `read(?Participant $participant = null)`
- Mark the "FROM" provider or given participant as read.

```php
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RTippin\Messenger\Facades\MessengerComposer;
use RTippin\Messenger\Http\Resources\MessageReactionResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;

public function testing(Request $request, Thread $thread): JsonResponse
{
    MessengerComposer::to($thread)->from($request->user())->read();

    return new JsonResponse([
        'message' => 'success'
    ]);
}
```

---

### `emitTyping()`
- Emit a typing presence client event.

```php
use Illuminate\Http\Request;
use RTippin\Messenger\Facades\MessengerComposer;
use RTippin\Messenger\Models\Thread;

public function testing(Request $request, Thread $thread): void
{
    MessengerComposer::to($thread)
      ->from($request->user())
      ->emitTyping()
      ->message('I emit the client-event of typing to the threads presence channel!');
}
```

---

### `emitStopTyping()`
- Emit a stopped typing presence client event.

```php
use Illuminate\Http\Request;
use RTippin\Messenger\Facades\MessengerComposer;
use RTippin\Messenger\Models\Thread;

public function testing(Request $request, Thread $thread): void
{
    MessengerComposer::to($thread)
      ->from($request->user())
      ->emitTyping()
      ->message('I emit the client-event of typing and stopped typing to the threads presence channel!');

    MessengerComposer::to($thread)->from($request->user())->emitStopTyping();
}
```

---

### `emitRead(?Message $message = null)`
- Emit a read/seen presence client event. Optional message instance can be used.

```php
use Illuminate\Http\Request;
use RTippin\Messenger\Facades\MessengerComposer;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;

public function testing(Request $request, Thread $thread, Message $message): void
{
    MessengerComposer::to($thread)->from($request->user())->emitRead($message);
}
```

---

## Client Presence Events

- Our backend has the ability to broadcast client events (`typing`|`stopped tyoing`|`read`) that your client side can listen for, just as if the client sent the whispers themselves.
- We have the base classes and data structure included, but you are able to override our defaults and set your own returned data and the broadcast class itself.

---

## Typing
```php
MessengerComposer::to($thread)->from($user)->emitTyping();
```
***Thread presence channel***
```js
Echo.join('messenger.thread.1234-5678').listenForWhisper('typing', handleTyping)
```
***Typing broadcast data***
```json
{
  "provider_id" : 1234,
  "provider_alias" : "user",
  "name" : "John Doe",
  "avatar" : "/path/to/avatar.jpg",
}
```

## Stopped Typing
```php
MessengerComposer::to($thread)->from($user)->emitStopTyping();
```
***Thread presence channel***
```js
Echo.join('messenger.thread.1234-5678').listenForWhisper('stop-typing', handleStoppedTyping)
```
***Stopped typing broadcast data***
```json
{
  "provider_id" : 1234,
  "provider_alias" : "user",
  "name" : "John Doe",
  "avatar" : "/path/to/avatar.jpg",
}
```

## Read / Seen
```php
MessengerComposer::to($thread)->from($user)->emitRead($message);
```
***Thread presence channel***
```js
Echo.join('messenger.thread.1234-5678').listenForWhisper('read', handleMessageSeen)
```
***Read broadcast data***
```json
{
  "provider_id" : 1234,
  "provider_alias" : "user",
  "name" : "John Doe",
  "avatar" : "/path/to/avatar.jpg",
  "message_id" : "1234-5678-9999",
}
```

---

## Overwriting Presence Event classes and data
- You will need to create a simple class that extends our abstract [MessengerBroadcast][link-messenger-broadcast] class.
- Set the name for the event, usually prefixed with `client-` to emulate a client event that your frontend will `listenForWhisper`.
- Once created, you will register it with our [PresenceEvents][link-presence-events] support class.
- Overwriting the data only requires you to set a new closure for each client event you choose.

#### New Classes
```php
<?php

namespace App\Broadcasting;

use RTippin\Messenger\Broadcasting\MessengerBroadcast;

class Typing extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'client-is-typing';
    }
}

class StopTyping extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'client-stopped-typing';
    }
}

class Read extends MessengerBroadcast
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'client-seen';
    }
}
```

#### Register overrides in your `MessengerServiceProvider` boot method
```php
<?php

namespace App\Providers;

use App\Broadcasting\Read;
use App\Broadcasting\StopTyping;
use App\Broadcasting\Typing;
use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Support\PresenceEvents;

class MessengerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //Setting our custom classes
        PresenceEvents::setTypingClass(Typing::class);
        PresenceEvents::setTypingClass(StopTyping::class);
        PresenceEvents::setTypingClass(Read::class);
        
        //Setting our own closures for data that will be broadcast.
        PresenceEvents::setTypingClosure(function (MessengerProvider $provider) {
            return [
                'id' => $provider->getKey(),
                'name' => $provider->getProviderName(),
                'typing' => true,
                'extra' => 'typing',
            ];
        });
        PresenceEvents::setStopTypingClosure(function (MessengerProvider $provider) {
            return [
                'id' => $provider->getKey(),
                'name' => $provider->getProviderName(),
                'typing' => false,
                'extra' => 'not typing',
            ];
        });
        PresenceEvents::setReadClosure(function (MessengerProvider $provider, ?Message $message = null) {
            return [
                'id' => $provider->getKey(),
                'name' => $provider->getProviderName(),
                'message_id' => optional($message)->id,
                'extra' => 'seen',
            ];
        });
    }
}
```

### Outputs
```php
MessengerComposer::to($thread)
  ->from($user)
  ->emitTyping()
  ->emitStopTyping()
  ->emitRead($message);
```
***Thread presence channel***
```js
Echo.join('messenger.thread.1234-5678')
  .listenForWhisper('is-typing', handleTyping)
  .listenForWhisper('stopped-typing', handleStoppedTyping)
  .listenForWhisper('seen', handleMessageSeen)
```
***Typing broadcast data***
```json
{
  "id" : 1234,
  "name" : "John Doe",
  "typing" : true,
  "extra" : "typing",
}
```
***Stopped typing broadcast data***
```json
{
  "id" : 1234,
  "name" : "John Doe",
  "typing" : false,
  "extra" : "not typing",
}
```
***Read / seen broadcast data***
```json
{
  "id" : 1234,
  "name" : "John Doe",
  "message_id" : "1234-5678-9999",
  "extra" : "seen",
}
```

[link-messenger-broadcast]: https://github.com/RTippin/messenger/blob/1.x/src/Broadcasting/MessengerBroadcast.php
[link-presence-events]: https://github.com/RTippin/messenger/blob/1.x/src/Support/PresenceEvents.php