# Events

---

- The majority of the core actions in this package will dispatch event(s). This gives you the flexibility to attach your own event listeners as needed.
- By default, specific events have already been subscribed to internally through our event subscribers. Depending on your config settings, these subscribers can be enabled or disabled.
  - [BotSubscriber][link-bot-subscriber]
  - [CallSubscriber][link-call-subscriber]
  - [SystemMessageSubscriber][link-system-message-subscriber]
- Note that all the events dispatched will come from the namespace `RTippin\Messenger\Events`

---

### `BotActionFailedEvent`
- Dispatched when a `BotActionHandler` fails being triggered, resulting in an exception. The exception will be caught and forwarded into this event.
  - `$event->action` : `BotAction` model that failed.
  - `$event->exception` : `Throwable` exception thrown.

---

### `BotActionHandledEvent`
- Dispatched when a `BotActionHandler` is successfully handled.
  - `$event->action` : `BotAction` model that was triggered.
  - `$event->message` : `Message` model that triggered the `BotAction`.
  - `$event->trigger` : `string` trigger that was matched on the `BotAction`.

---

### `BotActionRemovedEvent`
- Dispatched when a `BotAction` is deleted.
  - `$event->provider` : `?MessengerProvider` nullable provider that deleted the `BotAction`.
  - `$event->action` : `array` data of the deleted `BotAction`.

---

### `BotActionUpdatedEvent`
- Dispatched when a `BotAction` is updated.
  - `$event->provider` : `MessengerProvider` provider that updated the `BotAction`.
  - `$event->action` : `BotAction` model that was updated.

---

### `BotArchivedEvent`
- Dispatched when a `Bot` is archived.
  - `$event->provider` : `MessengerProvider` provider that archived the `Bot`.
  - `$event->bot` : `Bot` model that was archived.

---

### `BotAvatarEvent`
- Dispatched when a `Bot` avatar is uploaded or removed.
  - `$event->provider` : `MessengerProvider` provider that updated the `Bot` avatar.
  - `$event->bot` : `Bot` model that had its avatar updated.

---

### `BotUpdatedEvent`
- Dispatched when a `Bot` has its settings uploaded.
  - `$event->provider` : `MessengerProvider` provider that updated the `Bot`.
  - `$event->bot` : `Bot` model that was updated.
  - `$event->originalName` : `string` original name of the `Bot` before being updated, should the bot be given a new name.

---

### `CallEndedEvent`
- Dispatched when a `Call` is ended.
  - `$event->provider` : `?MessengerProvider` nullable provider that ended the `Call`.
  - `$event->call` : `Call` model that was ended.

---

### `CallIgnoredEvent`
- Dispatched when a `Call` is ignored.
  - `$event->provider` : `MessengerProvider` provider that ignored the `Call`.
  - `$event->call` : `Call` model that was ignored.

---

### `CallJoinedEvent`
- Dispatched when a `CallParticipant` joins/re-joins a `Call`.
  - `$event->participant` : `CallParticipant` model that joined the `Call`.
  - `$event->call` : `Call` model that was joined.

---



[link-system-message-subscriber]: https://github.com/RTippin/messenger/blob/1.x/src/Listeners/SystemMessageSubscriber.php
[link-call-subscriber]: https://github.com/RTippin/messenger/blob/1.x/src/Listeners/CallSubscriber.php
[link-bot-subscriber]: https://github.com/RTippin/messenger/blob/1.x/src/Listeners/BotSubscriber.php