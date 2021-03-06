# Changelog
- All notable changes to `Messenger` will be documented in this file.

---

### [v0.42 (07-14-2021)]

#### Added
- `message_size_limit` to our config. ([bb87b51](https://github.com/RTippin/messenger/commit/bb87b51e7395624d3c35afd16fbbce015e4a3a5d))
- `isGroupAdmin` property to the `BotActionHandler`. ([1f6f51b](https://github.com/RTippin/messenger/commit/1f6f51b3a2b669302bbc19d78629ed7804812bdb))

#### Changed
- Implemented `message_size_limit` to any message send or edit message request.
- Forward `isGroupAdmin` from event -> process triggers to `BotActionHandler`. Interface updated.

---

### [v0.41 (07-06-2021)]

#### Added
- `getFirstValidEmojiShortcode` method to the Emoji interface. ([cc28e63](https://github.com/RTippin/messenger/commit/cc28e63497bb51bd06202750d920609afc313f95))
- `emitTyping`, `emitStopTyping`, `emitRead`, and `read` methods to `MessengerComposer`. ([36ccbcf](https://github.com/RTippin/messenger/commit/36ccbcff056a876083eda32a79488c7be3ebdec6))
- [PresenceEvents](https://github.com/RTippin/messenger/blob/101bdeee48acdd647a19ac18bce8b5604af71e70/src/Support/PresenceEvents.php) class to handle getting/setting client presence events.

#### Changed
- Improve (reduced) queries for message reactions. ([189b1cf](https://github.com/RTippin/messenger/commit/189b1cfb560fd02f85f7b8e273daa702dcfe4644))
- Methods for checking reflection moved from trait to helper class as static methods. ([c06f299](https://github.com/RTippin/messenger/commit/c06f2992fbacf52ca3d633f0f306ca921651cae0) 
  & [1b5ee7d](https://github.com/RTippin/messenger/commit/1b5ee7d8179e4d232c7af5692f0b8da51d13f1d7))

#### Removed
- `ChecksReflection` trait.
- `setAction`, `setThread`, `setMessage` methods from `BotActionHandler`. Replaced with `setDataForMessage`. ([4a5acdf](https://github.com/RTippin/messenger/commit/4a5acdf36dd5ef3ee75ff57b4d7f83baa43d8d2f))

---

### [v0.40 (07-05-2021)]

#### Added
- `BotActionHandledEvent` and `BotActionFailedEvent`. ([d5ab76c](https://github.com/RTippin/messenger/commit/d5ab76c1ad684367291badb3f6452ef751f70ec5))
- `Bots` api documentation.

#### Changed
- Add index to column `bot_actions.handler`
- Moved logic for executing action handlers to `ProcessMessageTrigger` action class, and using new query. ([39664d6](https://github.com/RTippin/messenger/commit/39664d6aa7a80ca9e0731b1019856d6ac9caf9bd))
- When a bot handler fails / throws exception, the exception will not be reported. The action and exception will be dispatched 
  in the new `BotActionFailedEvent` that can be listened to by the end user.
- Fixed bot and bot action cooldowns to cast as integer.

---

### [v0.39 (07-03-2021)]

#### Added
- [MessengerComposer](https://github.com/RTippin/messenger/blob/9e4560a64977fdc563d23a714fed1f50b1dce1ec/src/Support/MessengerComposer.php) class and facade. 
  Easily build to/from and message/image/document/audio/reaction/knock.
- `composer()` method to the core [BotActionHandler](https://github.com/RTippin/messenger/blob/9e4560a64977fdc563d23a714fed1f50b1dce1ec/src/Actions/Bots/BotActionHandler.php#L139)

---

### [v0.38 (07-01-2021)]

#### Changed
- Bug fix for bots when end user providers using INT keys and not UUIDs. Bot primary key now matches end 
  users messenger config `provider_uuids`. ([9c3ae1a](https://github.com/RTippin/messenger/commit/9c3ae1a469e61e6a028c5757dea05be08880c90c))
- Improved query performance for scopes providers. ([f093d23](https://github.com/RTippin/messenger/commit/f093d23e7e1e22c13ca4e45d2597a46e97203f80))

#### Removed
- `concatBuilder` from [ScopesProviders](https://github.com/RTippin/messenger/blob/664b991b05aa40bb0cdf24c8cea5a2171aef80c9/src/Traits/ScopesProvider.php#L111) trait.

---

### [v0.37 (06-29-2021)]

#### Added
- System messages for bot events (create, delete, avatar, rename).
- Proper store and destroy group thread avatar.
- `avatars` and `default_thread_avatar` in `files` config array.
- New methods for avatars (provider, thread, bot)
- Please see config: [messenger.php](https://github.com/RTippin/messenger/blob/acb8e5af518105d8c32684c387a48bbe6153e87c/config/messenger.php#L172)

#### Changed
- Janus server bug fixes with improper types properties when http call fails.
- Avatars (provider, thread, bot) share one config for size/mimes.
- Switched main queries used for locating threads/calls from using `whereHas` to custom scope using `join`.

#### Removed
- Random of 5 default group thread avatars. Only one set now.
- `thread_avatars` `provider_avatars` `default_thread_avatars` from `files` config array.
- Following methods from Messenger: `getThreadAvatarSizeLimit` `setThreadAvatarSizeLimit` 
  `getThreadAvatarMimeTypes` `setThreadAvatarMimeTypes` `isProviderAvatarRemovalEnabled` 
  `setProviderAvatarRemoval` `getProviderAvatarSizeLimit` `setProviderAvatarSizeLimit` 
  `getProviderAvatarMimeTypes` `setProviderAvatarMimeTypes`

---

### [v0.36 (06-24-2021)]

#### Added
- `assets` config array under the `routing` config section.
- `assets.php` routing file.
- `senderIp` to `NewMessageEvent`. Forward IP to bot handlers.

#### Changed
- Assets (avatars/images/documents) no longer require authorization.
- Removed the bool parameter for api on the method`getProviderAvatarRoute()` from the `MessengerProvider` contract.

#### Removed
- Assets, views, web routes. Relocated to [Messenger UI](https://github.com/RTippin/messenger-ui) addon.
- All API prefixed routes and responses for assets (images, documents, audio, avatars).
- `site_name` from config.
- `web` from `routing` config.
- `provider_avatar` from `routing` config.
- `getSiteName()` `getWebEndpoint()` `isWebRoutesEnabled()` and `getSocketEndpoint()` methods from Messenger.

---

### [v0.35 (06-20-2021)]

#### Added
- Group thread bots feature!
- `Bot` and `BotAction` models.
- `bots` config array
- `default_bot_avatar` to files config array.
- Migrations for `bots` and `bot_actions`.
- `MessengerBots` facade.
- Default bot avatar in published images.
- global helpers for `broadcaster()` and `bots()`.
- `messenger:purge:bots` command.

#### Changed
- Thread `activeCall` relationship to using proper `ofMany` query.
- `messenger:providers:cache` command caches providers with the merged bot provider.
- Switched injecting cache manager and config repository to using facade/helpers.
- See above for `messenger.php` config changes.
- upgraded UI packages, edited views, and recompiled assets.
- `SystemFeaturesResource` implementation changed to using a direct call to the messenger service class.
- When saving a group threads settings or updating a participants permissions, feature disabled values will be ignored.

#### Removed
- `SystemFeaturesResource` json resource.

---

### [v0.34 (06-03-2021)]

#### Added
- Event subscribers for calling and system messages. More config for these to toggle on or off, set queued, and queue channel.
- Config option to toggle system messages on or off.
- boolean column `chat_bots` on `threads` table.
- boolean column `manage_bots` on `participants` table.
- `SystemFeaturesResource` attached to threads collection and thread resource. Shows the current system feature statuses.
- Groundwork for upcoming bots feature.

#### Changed
- Default broadcast driver set in the service provider.
- `push_notifications` in config no longer nested array.

#### Removed
- Drivers section from config.
- ALL queued listeners and the Event => Listener map.
- `getBroadcastDriver()` and `getVideoDriver()` methods from messenger.

---

### [v0.33 (05-24-2021)]

#### Changed
- Any models using `protected $dates` were switched to using cast for `datetime`
- Storing invite now accepts null or any valid timestamp more than 5 minutes in the future, for the `expires` time.

---

### [v0.32 (05-18-2021)]

#### Added
- Using new `latestOfMany` eloquent call to gather the latest message for each thread in eager loads.

#### Changed
- composer.json now locked to laravel ^8.42
- Rename relation on thread `recentMessage` to `latestMessage`

#### Removed
- staudenmeir/eloquent-eager-limit dependency.

---

### [v0.31 (05-13-2021)]

#### Changed
- Fine tuned MessageTransformer. Bug fixes on some sentences.
- Renamed ALL methods on the MessengerProvider contract and matching trait to avoid future conflicts with other laravel packages / implementations.
    - `name()` to `getProviderName()`
    - `getAvatarColumn()` to `getProviderAvatarColumn()`
    - `getLastActiveColumn()` to `getProviderLastActiveColumn()`
    - `getAvatarRoute()` to `getProviderAvatarRoute()`
    - `getRoute()` to `getProviderProfileRoute()`
    - `onlineStatus()` to `getProviderOnlineStatus()`
    - `onlineStatusVerbose()` to `getProviderOnlineStatusVerbose()`

---

### [v0.30 (05-11-2021)]

#### Added
- MessageTransformer support class to generate and transform message body.

#### Changed
- Bugfix in JS where failed message image loaded improper not found image.
- Yarn upgrade. Assets recompiled.

#### Removed
- Message transformer methods from MessageResource class.
- `.svg` as a valid default image mime allowed for upload.

---

### [v0.29 (05-02-2021)]

#### Changed
- FileService now resets properties after each `upload`, `destroy` and `destroyDirectory`.

#### Removed
- `getName()` method removed from FileService. `upload()` method returns final file name now.

---

### [v0.28 (04-22-2021)]

#### Added
- Support for morph maps on polymorphic relations for providers.

#### Changed
- All instances of `get_class()` replaced with `$model->getMorphClass()`.

#### Removed
- `getProviderId()` and `getProviderClass()` methods removed from Messenger.

---

### [v0.27 (04-21-2021)]

#### Removed
- StoreMessengerIp listener removed. Up to end user to attach listener to heartbeat event should they want to use the IP provided.

#### Changed
- `instance()` method on messenger singleton renamed to `getInstance()`
- General major refactoring.

---

### [v0.26 (04-12-2021)]

#### Added
- bool column `embeds` and nullable/text column `extra` on `messages` table.
- Optional extra payload when sending messages to allow custom json to be stored with the message.
- More extensive model factories.
- New routes for viewing group avatar when joining with an invitation.

#### Changed
- Renamed action class UpdateMessage to EditMessage.

---

### [v0.25 (04-10-2021)]

#### Added
- Ignore call option. When ignoring a private call, it will also end the call.

#### Changed
- Either party in a private call can end the call.

---

### [v0.24 (04-05-2021)]

#### Added
- Message reactions feature. New table `message_reactions`.
- Events/broadcast for reactions feature.
- bool `edited` and `reacted` columns on messages table.
- New providers scope using concat() on polymorph keys.
- Custom rule to verify an emoji exist in string.

#### Changed
- Message edits go by bool `edited` and not `updated_at` column now.
- Emoji converter is now an interface/service.
- New emoji picker added to the included UI.
- Emoji converter is now a service/interface.

---

### [v0.23 (03-24-2021)]

#### Added
- Message replies.
- reply_to_id column on the messages table.

#### Changed
- All store message actions accept params as array now instead of individual params.

---

### [v0.22 (03-15-2021)]

#### Added
- Audio message type to upload audio files.
~ Configs for audio files.
- Command to purge archived audio files.
- Routes to store/view/paginate and stream/download audio files.

#### Changed
- Updated the UI to include support for audio files in a thread.

#### Removed
- `message_documents.download` toggle in config removed.

---

### [v0.21 (03-11-2021)]

#### Added
- New configs to set mime types allowed to upload on each file type.

#### Changed
- threads, messages, participants and message_edits tables use precision 6 for timestamps now.

---

### [v0.20 (03-09-2021)]

#### Changed
- Allow more mime types on uploads, frontend assets updated with this as well.
- Misc bug fixes along with image service not resizing gif/svg/webp.

---

### [v0.19 (02-28-2021)]

#### Added
- New configs to set upload limit sizes.
- New commands to temporarily shutdown calling system and end all active calls, as well as put the system back online.

---

### [v0.18 (02-20-2021)]

#### Changed
- More file moves/renames.
- Set broadcast/video driver on demand.

---

### [v0.17 (02-19-2021)]

#### Removed
- helpers.php methods except messenger().

#### Changed
- New Helpers class and support directory. Moved files around.
- Added intermediate modal to confirm joining call once page loads.

---

### [v0.16 (02-17-2021)]

#### Changed
- Bugfix on join call skipping generating participant resource on response.
- Broadcast broker resets private/presence each time to method called.

---

### [v0.15 (02-14-2021)]

#### Removed
- StoreEditMessage listener.

#### Changed
- Edit message action will store the edit history immediately after update message.
- Put a primary key back onto messenger model and table messengers.

---

### [v0.14 (02-09-2021)]

#### Added
- New Exceptions.

#### Changed
- Exceptions thrown throughout out package.
- Moved some authorization logic done in controllers or model into actions.

---

### [v0.13 (02-07-2021)]

#### Added
- Edit message table to store edit history.
- Route to view edit history.
- Config option to disable both edit message and viewing edit history.

---

### [v0.12 (02-05-2021)]

#### Added
- Edit message feature.

---

### [v0.11 (02-03-2021)]

#### Changed
- `teardown_complete` added to calls table used to avoid duplicate tear downs. Added a short cache lockout upon ending a call to avoid race conditions with automated EndCallIfEmpty job.

---

### [v0.10 (02-03-2021)]

#### Changed
- To avoid conflicts with channel names across apps, our channels are now prefixed with `messenger.` All impacted test and frontend assets have been updated to reflect this change.

---

### [v0.9 (02-01-2021)]

#### Added
- Test.

---

### [v0.1]

#### Added
- Everything.
