# Events

---

- Most core actions in this package will dispatch event(s). This gives you the flexibility to attach your own event listeners as needed.
- By default, specific events have already been subscribed to internally through the event subscribers. Depending on your config settings, these subscribers can be enabled or disabled.
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
  - `$event->trigger` : `string|null` trigger that was matched on the `BotAction`.

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

### `BroadcastFailedEvent`
- Dispatched when a broadcast fails and throws an exception. The exception will be caught and forwarded into this event.
  - `$event->abstractBroadcast` : Abstract `MessengerBroadcast` class `string` being broadcasted.
  - `$event->channels` : `array` of channels for the broadcast.
  - `$event->with` : `array` of data to be broadcasted.
  - `$event->exception` : `Throwable` exception thrown.

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

### `CallLeftEvent`
- Dispatched when a `CallParticipant` left `Call`.
  - `$event->participant` : `CallParticipant` model that left the `Call`.
  - `$event->call` : `Call` model that was left.

---

### `CallStartedEvent`
- Dispatched when a `Call` is created.
  - `$event->thread` : `Thread` model the `Call` belongs to.
  - `$event->call` : `Call` model that was created.

---

### `DemotedAdminEvent`
- Dispatched when a `Participant` is demoted from group admin.
  - `$event->provider` : `MessengerProvider` provider that demoted the `Participant`.
  - `$event->thread` : `Thread` model the `Participant` belongs to.
  - `$event->participant` : `Participant` model that was demoted.

---

### `EmbedsRemovedEvent`
- Dispatched when embeds on a `Message` are removed.
  - `$event->provider` : `MessengerProvider` provider that removed embeds.
  - `$event->message` : `Message` model embeds were removed from.

---

### `FriendApprovedEvent`
- Dispatched when a `PendingFriend` is accepted.
  - `$event->friend` : `Friend` model that was created.
  - `$event->inverseFriend` : `Friend` inverse model that was created.

---

### `FriendCancelledEvent`
- Dispatched when a `SentFriend` is cancelled.
  - `$event->friend` : `SentFriend` model that was deleted.

---

### `FriendDeniedEvent`
- Dispatched when a `PendingFriend` is denied.
  - `$event->friend` : `PendingFriend` model that was denied.

---

### `FriendRemovedEvent`
- Dispatched when a `Friend` is removed.
  - `$event->friend` : `Friend` model being removed.
  - `$event->inverseFriend` : `?Friend` nullable inverse model to the `Friend` removed.

---

### `FriendRequestEvent`
- Dispatched when a `SentFriend` is created.
  - `$event->friend` : `SentFriend` model that was created.

---

### `InviteArchivedEvent`
- Dispatched when an `Invite` is archived.
  - `$event->provider` : `?MessengerProvider` nullable provider that archived the `Invite`.
  - `$event->invite` : `Invite` model that was archived.

---

### `InviteUsedEvent`
- Dispatched when an `Invite` is used to join a `Thread`.
  - `$event->provider` : `MessengerProvider` provider that used the `Invite` to join the `Thread`.
  - `$event->thread` : `Thread` model the `Invite` belongs to.
  - `$event->invite` : `Invite` model used to join the `Thread`.

---

### `KickedFromCallEvent`
- Dispatched when a `CallParticipant` is kicked or un-kicked.
  - `$event->provider` : `MessengerProvider` provider that kicked/un-kicked the `CallParticipant`.
  - `$event->participant` : `CallParticipant` model that was kicked/un-kicked.
  - `$event->call` : `Call` model the `CallParticipant` belongs to.

---

### `KnockEvent`
- Dispatched when a knock is sent to a `Thread`.
  - `$event->provider` : `MessengerProvider` provider that sent the knock.
  - `$event->thread` : `Thread` model the knock was sent to.

---

### `MessageArchivedEvent`
- Dispatched when a `Message` is archived.
  - `$event->provider` : `MessengerProvider` provider archived the `Message`.
  - `$event->message` : `Message` model that was archived.

---

### `MessageEditedEvent`
- Dispatched when a `Message` is edited.
  - `$event->message` : `Message` model that was archived.
  - `$event->originalBody` : `?string` nullable original body of the message being edited.

---

### `NewBotActionEvent`
- Dispatched when a `BotAction` is created.
  - `$event->botAction` : `BotAction` model that was created.

---

### `NewBotEvent`
- Dispatched when a `Bot` is created.
  - `$event->bot` : `Bot` model that was created.

---

### `NewInviteEvent`
- Dispatched when an `Invite` is created.
  - `$event->invite` : `Invite` model that was created.

---

### `NewMessageEvent`
- Dispatched when a `Message` is created.
  - `$event->message` : `Message` model that was created.
  - `$event->thread` : `Thread` model the `Message` belongs to.
  - `$event->isGroupAdmin` : `bool` whether the message sender is a group thread admin or not.
  - `$event->senderIp` : `?string` nullable IP of the message sender.

---

### `NewThreadEvent`
- Dispatched when a `Thread` is created.
  - `$event->provider` : `MessengerProvider` provider that created the `Thread`.
  - `$event->thread` : `Thread` model that was created.

---

### `PackagedBotInstalledEvent`
- Dispatched after a packaged bot was successfully installed in a thead.
  - `$event->packagedBot` : `PackagedBotDTO` class.
  - `$event->thread` : `Thread` model the package was installed in.
  - `$event->provider` : `MessengerProvider` provider who installed the package.

---

### `ParticipantMutedEvent`
- Dispatched when a `Participant` mutes its `Thread`.
  - `$event->participant` : `Participant` model that was muted.

---

### `ParticipantPermissionsEvent`
- Dispatched when a `Participant` permissions is updated.
  - `$event->provider` : `MessengerProvider` provider that updated the `Participant` permissions.
  - `$event->thread` : `Thread` model the `Participant` belongs to.
  - `$event->participant` : `Participant` model that was updated.

---

### `ParticipantReadEvent`
- Dispatched when a `Participant` marks a `Thread` as read.
  - `$event->participant` : `Participant` model that was marked read.

---

### `ParticipantsAddedEvent`
- Dispatched when adding participants to a `Thread`.
  - `$event->provider` : `MessengerProvider` provider adding the participants.
  - `$event->thread` : `Thread` model the added participants belong to.
  - `$event->participants` : `Collection` of `Participant` models that were added to the `Thread`.

---

### `ParticipantUnMutedEvent`
- Dispatched when a `Participant` un-mutes its `Thread`.
  - `$event->participant` : `Participant` model that was un-muted.

---

### `PromotedAdminEvent`
- Dispatched when a `Participant` is promoted to a group admin.
  - `$event->provider` : `MessengerProvider` provider that promoted the `Participant`.
  - `$event->thread` : `Thread` model the `Participant` belongs to.
  - `$event->participant` : `Participant` model that was promoted.

---

### `PushNotificationEvent`
- Dispatched when any `private channel` broadcast is emitted.
  - `$event->broadcastAs` : `string` broadcast event name.
  - `$event->recipients` : `Collection` of all recipient `ID/TYPE` the broadcast was sent to.
  - `$event->data` : `array` data that was sent in the broadcast.

---

### `ReactionAddedEvent`
- Dispatched when a `MessageReaction` is created.
  - `$event->reaction` : `MessageReaction` model that was created.

---

### `ReactionRemovedEvent`
- Dispatched when a `MessageReaction` is deleted.
  - `$event->provider` : `MessengerProvider` provider that deleted the `MessageReaction`.
  - `$event->reaction` : `array` data of the removed `MessageReaction` model.

---

### `ReactionRemovedEvent`
- Dispatched when a `MessageReaction` is deleted.
  - `$event->provider` : `MessengerProvider` provider that deleted the `MessageReaction`.
  - `$event->reaction` : `array` data of the removed `MessageReaction` model.

---

### `RemovedFromThreadEvent`
- Dispatched when a `Participant` is removed from a `Thread`.
  - `$event->provider` : `MessengerProvider` provider that removed the `Participant`.
  - `$event->thread` : `Thread` model the `Participant` was removed from.
  - `$event->participant` : `Participant` model that was removed.

---

### `StatusHeartbeatEvent`
- Dispatched when a `MessengerProvider` hits the `heartbeat` endpoint.
  - `$event->provider` : `MessengerProvider` provider using the `heartbeat`.
  - `$event->IP` : `string` IP from the `MessengerProvider`.
  - `$event->away` : `bool` whether the `MessengerProvider` is away/idle or not.

---

### `ThreadApprovalEvent`
- Dispatched when a private `Thread` approval is approved or denied.
  - `$event->provider` : `MessengerProvider` provider that approved/denied the approval.
  - `$event->thread` : `Thread` model being approved/denied.
  - `$event->approved` : `bool` whether the `Thread` was approved or denied.

---

### `ThreadArchivedEvent`
- Dispatched when a `Thread` is archived.
  - `$event->provider` : `?MessengerProvider` nullable provider that archived the `Thread`.
  - `$event->thread` : `Thread` model that was archived.

---

### `ThreadAvatarEvent`
- Dispatched when a `Thread` avatar is uploaded or removed.
  - `$event->provider` : `MessengerProvider` provider that updated the `Thread` avatar.
  - `$event->thread` : `Thread` model that was updated.

---

### `ThreadLeftEvent`
- Dispatched when a `Participant` leaves a group `Thread`.
  - `$event->provider` : `MessengerProvider` provider that left the `Thread`.
  - `$event->thread` : `Thread` model that was left.
  - `$event->participant` : `Participant` model that left the `Thread`.

---

### `ThreadSettingsEvent`
- Dispatched when a group `Thread` settings is updated.
  - `$event->provider` : `MessengerProvider` provider that updated the `Thread` settings.
  - `$event->thread` : `Thread` model being updated.
  - `$event->nameChanged` : `bool` whether the `Thread` name was changed or not.

---


[link-system-message-subscriber]: https://github.com/RTippin/messenger/blob/1.x/src/Listeners/SystemMessageSubscriber.php
[link-call-subscriber]: https://github.com/RTippin/messenger/blob/1.x/src/Listeners/CallSubscriber.php
[link-bot-subscriber]: https://github.com/RTippin/messenger/blob/1.x/src/Listeners/BotSubscriber.php