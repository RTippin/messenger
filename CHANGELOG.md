# Changelog
- All notable changes to `Messenger` will be documented in this file.

---

### [v1.21.0 (04-25-2022)](https://github.com/RTippin/messenger/compare/v1.20.0...v1.21.0)

#### Added
- Atomic locks when starting or ending a call.
- Uploaded file name sanitization.

#### Changed
- `FileService` no longer names a file based on a `type` specified.
- Starting and ending a call no longer use simple cache keys for their lockouts. Atomic locks are now used.
  - Due to this, your cache driver must support atomic locks. See: [https://laravel.com/docs/9.x/cache#atomic-locks](https://laravel.com/docs/9.x/cache#atomic-locks)
- Deprecated:
  - `setCallLockout` method in the `NewCallAction`. This method now does nothing and is no longer needed.
  - `setType` method in `FileService`. This method now does nothing and is no longer needed.
  - All `TYPE_` constants in `FileService`.

---

### [v1.20.0 (02-08-2022)](https://github.com/RTippin/messenger/compare/v1.19.0...v1.20.0)

#### Changed
- Updated dependencies for `laravel ^9.x` and `PHP ^8.0.2`.

---

### [v1.19.0 (01-12-2022)](https://github.com/RTippin/messenger/compare/v1.18.0...v1.19.0)

#### Added
- `getEmptyResponse` to `BaseMessengerAction`.

#### Changed
- All `DELETE` routes now only return an empty response with a `204` status code. (Previously some returned json resources or json success messages)
- Renamed `getMessageResponse` to `getSuccessResponse` in `BaseMessengerAction`.

---

### [v1.18.0 (01-05-2022)](https://github.com/RTippin/messenger/compare/v1.17.0...v1.18.0)

#### Added
- `Handler.php` to decorate the exception handler resolved from the container.

#### Changed
- All rate-limiting middleware is now defined directly on the routes and not within controller constructors.
- Knocking uses a proper rate limiter now, throwing a throttle exception with the remaining seconds.
- `ModelNotFound` exceptions thrown from messenger routes will be transformed into a sanitized message.
- Routes now use the `scopedBindings` flag instead of defining them per route.
- Minimum laravel version bumped to `^8.70`.

#### Removed
- Knock timeout setter/getter from `Thread.php` model.

---

### [v1.17.0 (12-23-2021)](https://github.com/RTippin/messenger/compare/v1.16.0...v1.17.0)

#### Added
- `getDTO` static method on both `BotActionHandler` and `PackagedBot`.
- `testResolve` static method on `BotActionHandler` for aid in testing.
- `testInstalls` static method on `PackagedBot` for aid in testing.
- `authorizeHandler` and `authorizePackagedBot` methods on `MessengerBots`.
- `shouldAuthorize` method on `MessengerBots`, allowing you to disable handler / bot package authorization for a single request cycle.
- Singular methods `getHandler` and `getPackagedBot` on `MessengerBots` for single resource fetches.

#### Changed
- `Uuids` trait now sets the `incrementing` and `keyType` properties for models that use it.
- Moved the namespace for the base `MessengerCollection`.
- Consolidated bot handler / packaged bot authorization handling methods.
- `GhostUser` primary ID is now static/unchanging.

---

### [v1.16.0 (12-16-2021)](https://github.com/RTippin/messenger/compare/v1.15.0...v1.16.0)

#### Changed
- `PackagedBotDTO.php` now applies filters for authorization, can install, and already installed.
- When viewing `PackagedBotDTO.php`'s through a request, `installed` and `already_installed` are now returned, where `already_installed` shows handlers flagged as unique that already exists in the thread.
- Various testing refactor and docblock improvements.

---

### [v1.15.0 (12-12-2021)](https://github.com/RTippin/messenger/compare/v1.14.0...v1.15.0)

#### Added
- `use_absolute_routes` config, default of false.
- `Messenger::shouldUseAbsoluteRoutes()` method you can override on the fly.
  - When `Messenger::flush()` is called, the absolute route flag will be reset to the initial config value.

#### Changed
- `BotActionHandler`'s attached to a `PackagedBot` will now be authorized when viewing / installing a `PackagedBot`.
- `FileService` now prefixes an image with its original file name during the renaming process.

---

### [v1.14.0 (12-10-2021)](https://github.com/RTippin/messenger/compare/v1.13.0...v1.14.0)

#### Added
- Packaged Bots feature! Please check the chat-bots documentation for more information.
- `Helpers::forProviderInCollection()` method.
- `BOT_PACKAGE_INSTALLED` system message type.
- `php artisan messenger:make:packaged-bot {name}` command.
- More state helpers on model factories.

#### Changed
- `BotActionHandler` class moved from `RTipppin\Messenger\Actions\Bots` to `RTippin\Messenger\Support`.
  - The `BotActionHandler` original is now deprecated and extends the new class's location.
- Renamed `MessengerBots::getHandlersDTO()` to `MessengerBots::getHandlers()`.
- Renamed `MessengerBots::getAlises()` to `MessengerBots::getHandlerAliases()`.
- Renamed `BotAction` method `getHandlersDTO` to `getHandler`.
- A bot handler flagged as unique may only be attached to a single bot in a group thread, not once per bot.

#### Removed
- `forProvider` collection macro.
- Redundant interfaces `Action`, `ActionHandler`, and `BroadcastEvent`.

---

### [v1.13.0 (11-11-2021)](https://github.com/RTippin/messenger/compare/v1.12.0...v1.13.0)

#### Added
- `MessengerBots::MATCH_ANY` match method.
  - When using `MATCH_ANY`, triggers are ignored and a handler will be handled for any message sent.
- `BotHandlerResolverService` to handle resolving / validating `BotAction` data for storing/updating.
- `BotAction::formatTriggers()` helper, logic relocated from `MessengerBots`.
- `BotActionHandlerDTO`, `ResolvedBotHandlerDTO`, and `MessengerProviderDTO` to structure internal data arrays.
- `MessengerBots::getHandlersDTO()` returns a collection or single instance of `BotActionHandlerDTO`.

#### Changed
- `BotActionHandledEvent` `trigger` property can now be `string|null`.
- `MessengerBots::getHandlerSettings()` deprecated. Use `getHandlersDTO`.
- `MessengerBots::getAuthorizedHandlers()` now returns a collection of `BotActionHandlerDTO` instances.

#### Removed
- `MessengerBots::resolveHandlerData()` method.

---

### [v1.12.0 (11-06-2021)](https://github.com/RTippin/messenger/compare/v1.11.0...v1.12.0)

#### Added
- `BroadcastFailedEvent` will be dispatched when a broadcast fails and throws an exception.

#### Changed
- Relocated knock cache methods to the `Thread` model from `SendKnock` action class.
- Various code improvements.

---

### [v1.11.0 (10-28-2021)](https://github.com/RTippin/messenger/compare/v1.10.0...v1.11.0)

#### Added
- Caching on `BotAction` collection for a group thread when jobs process matching bot triggers against messages sent.

#### Changed
- All bot action classes will clear the `BotAction` cache.
- Enforce checking child to parent relation keys in policies.
- All route model bindings are now scoped.
- Job class properties now have `public` visibility.

---

### [v1.10.0 (10-22-2021)](https://github.com/RTippin/messenger/compare/v1.9.0...v1.10.0)

#### Added
- Caching to a `Messages`'s `replyTo` relation.
- Updating or archiving a message will reset the `replyTo` cache key.

#### Changed
- Moved cache methods and keys to model helpers.
- `Bot` name method now uses `htmlspecialchars`.

#### Removed
- `CallHeartbeat.php` action class. 

---

### [v1.9.0 (10-07-2021)](https://github.com/RTippin/messenger/compare/v1.8.0...v1.9.0)

#### Added
- Constants on `MessengerBots` class for all bot matching methods.
- `getParsedMessage` and `getParsedWords` helpers on `BotActionHandler`|`ActionHandler`.
- `php artisan messenger:make:bot {name}` command.
- `body` state helper on `MessageFactory`.

#### Changed
- Renamed `setDataForMessage` to `setDataForHandler` on `BotActionHandler`|`ActionHandler`.
- Implemented helpers on the `BotAction.php` model for triggers and match method that take into account a handler classes overrides before using what is stored in the database.

---

### [v1.8.0 (10-02-2021)](https://github.com/RTippin/messenger/compare/v1.7.0...v1.8.0)

#### Added
- Caching for a participants last seen message.
- `thread_verifications` config section which defines whether friendship checks are enabled when creating a private thread or adding participants to a group thread.

```php
'thread_verifications' => [
    'private_thread_friendship' => env('MESSENGER_VERIFY_PRIVATE_THREAD_FRIENDSHIP', true),
    'group_thread_friendship' => env('MESSENGER_VERIFY_GROUP_THREAD_FRIENDSHIP', true),
],
```

#### Changed
- Various code improvements and bug fixes.

---

### [v1.7.0 (09-15-2021)](https://github.com/RTippin/messenger/compare/v1.6.0...v1.7.0)

#### Added
- Video message upload support `Message::VIDEO_MESSAGE | type 4`.
  - `StoreVideoMessage` action.
  - `video()` method to `MessengerComposer`
  - `messenger:purge:videos` command and accompanying job/action.
  - Private threads can be created using a video message.

#### Changed
- `assets` prefix on our asset route names (not paths).
- Eager loading of message reactions for message collections.

#### Notes / Updating
- This update is backwards compatible, however, video messages will be disabled by default until you add the new nested values to your published `messenger.php` configs `files` array.

```php
'files' => [
    // Add new message_videos
    'message_videos' => [
        'upload' => env('MESSENGER_MESSAGE_VIDEO_UPLOAD', true),
        'size_limit' => env('MESSENGER_MESSAGE_VIDEO_SIZE_LIMIT', 15360),
        'mime_types' => env('MESSENGER_MESSAGE_VIDEO_MIME_TYPES', 'avi,mp4,ogv,webm,3gp,3g2,wmv,mov'),
    ],
],
```

---

### [v1.6.0 (09-10-2021)](https://github.com/RTippin/messenger/compare/v1.5.0...v1.6.0)

#### Changed
- `MessengerComposer` will now emit events/broadcast when a new thread is created. The new thread will also now be marked as pending if the two providers are not friends.
- `storage.threads.disk` config default changed to `public` from `messenger`.
- Deprecated `getProviderOnlineStatusVerbose` from our `MessengerProvider` interface.
- Moved `shouldUseUuids` static method from `MessengerBots` to `Messenger` class.
- CS fixer updates.

---

### [v1.5.0 (09-05-2021)](https://github.com/RTippin/messenger/compare/v1.4.0...v1.5.0)

#### Added
- `FriendRemovedBroadcast` when a friend is removed.
- `withoutEvents` boolean flag on `MessengerComposer::silent(bool $withoutEvents = false)` to disable both broadcast and events for an action.

#### Changed
- `AuthenticateOptional::class` | `auth.optional` middleware deprecated.
- `Invite` model's `code` increased to 10 characters in length on creation.
- Code cleanup / typo fixes.

---

### [v1.4.0 (08-31-2021)](https://github.com/RTippin/messenger/compare/v1.3.0...v1.4.0)

#### Added
- New constants on `Thread`, `Participant`, `Message`, and `Call` models.
- New constants on `FriendDriver` and `MessengerProvider` interfaces.
- New constants on `FileService`.

#### Changed
- `Definitions` support class has been deprecated.
- All constants from `Definitions` have been moved to their respective model/interface.

---

### [v1.3.0 (08-29-2021)](https://github.com/RTippin/messenger/compare/v1.2.0...v1.3.0)

#### Added
- `NullFriendBroker` stand-in class for `FriendDriver`.
- `setFriendDriver` method on our core `Messenger` service.
- `AttachMessengersCommand` to attach our messenger models with existing records.

---

### [v1.2.0 (08-25-2021)](https://github.com/RTippin/messenger/compare/v1.1.1...v1.2.0)

#### Added
- New `scoped` implementation for setting/unsetting the `MessengerProvider`. Methods `setScopedProvider()` and `unsetScopedProvider()`.
  - A scoped provider will keep any prior provider set in memory, such as an authenticated user, while returning the scoped provider to any subsequent calls to `getProvider()`.
  - When unsetting a scoped provider, it will set the prior provider back to the active provider, if one was set.
  - This is mostly used internally, and on our `MessengerComposer` class, to avoid conflicts on queues as our `Messenger` service is a singleton.
- Interfaces `Ownerable`, `HasPresenceChannel` for use with internal models and broadcasting.

#### Changed
- Our broadcaster and push notification service will now reset their states after execution.
- When broadcasting, we now enforce unique channels, filtering any duplicates before firing any broadcast.
- Updated policy allow/deny responses.
- Unsetting the `MessengerProvider` will now flush any active `FriendDriver` from the container.
- Various bug fixes and request lifecycle improvements.

---

### [v1.1.1 (08-22-2021)](https://github.com/RTippin/messenger/compare/v1.1.0...v1.1.1)

#### Changed
- Fixed `ThreadArchivedMessage` to not broadcast the system message, as participants would have already received the archived broadcast.
- Updated policy allow/deny responses.
- `messenger:install` command will now ask for confirmation, and has the ability to overwrite our published config file, setting `provider_uuids` to true, using the `--uuids` flag. It will also ask if you want to migrate after publishing.
- General code improvements.

---

### [v1.1.0 (08-21-2021)](https://github.com/RTippin/messenger/compare/v1.0.2...v1.1.0)

#### Added
- `flush()` method on `Messenger` and `MessengerBots`.
- `BaseMessengerJob` all job classes now extend.
- `FlushMessengerServices` job middleware which flushes messenger and bot services. This fixes/prevents a queue worker's process from having one job alter our singleton's state, impacting a following job that calls to our singleton.

#### Changed
- `setVideoDriver` and `setBroadcastDriver` fixed to use `bind` and not `singleton`.
- Our `NullVideoBroker` will be set as our default `VideoDriver`.
- `ProcessMessageTriggers` properly flushes the active handler on our bots service between each match.

---

### [v1.0.2 (08-17-2021)]

#### Removed
- Removed deprecated `ViewPortalController` as it now resides in our UI addon package.  ([182a37a](https://github.com/RTippin/messenger/commit/182a37a5a02f3ffb1eb80cf810e6428798cedb4b))

#### Added
- Improved docs. ([9f6ab30](https://github.com/RTippin/messenger/commit/9f6ab30514e2853fc3e23d25f45f6c000234d9ff))

---

### [v1.0.1 (08-16-2021)]

#### Added
- `messengerComposer()` helper. ([13b4d9d](https://github.com/RTippin/messenger/commit/13b4d9d092e4a38db2538b50f0cc55ae15ac2404))
- `getUniqueHandlerClasses` to `MessengerBots`.
- `validUniqueActions` relationship on the `Bot` model.
- Expanded docs.

#### Changed
- When viewing `add-handlers` for a bot, we now filter out handlers marked as unique that are already attached to the bot. ([d643df3](https://github.com/RTippin/messenger/commit/d643df33404d9c2a4e1b709e4088655cc56a18d2))

---

### [v1.0.0 (08-13-2021)]

#### Version 1 released.

---

### [v0.47 (08-11-2021)]

#### Changed
- `threads.subject` and `bots.name` columns locked to max length of `255`.
- `Thread` `subject` validation now allows min length of 2 and max length of `255`.
- `Message` `temporary_id` post value set to max length of `255`.
- Commit ([0b96afd](https://github.com/RTippin/messenger/commit/0b96afddb8561b4ce8ac01d1cb73918b995e531e))

---

### [v0.46 (08-09-2021)]

#### Removed
- `JanusServer` and `VideoRoomService`.
- `janus` config file.
- `JanusBroker` video driver.
- Commit ([4ced3a9](https://github.com/RTippin/messenger/commit/4ced3a98fbff9d16933cf76d9bc0d08465f93802))

#### Notes
- If you were already using our provided janus, you must now create and register your own [JanusBroker](https://github.com/RTippin/messenger/blob/f4adbd94ab5dc33f8dc7257b7dc077cb2b2ea179/src/Brokers/JanusBroker.php)
- Install our new [janus-client](https://github.com/RTippin/janus-client) package and update your broker accordingly to use the packages create/destroy for `VideoRoom`.

---

### [v0.45 (08-04-2021)]

#### Changed
- `messages.body` and `message_edits.body` columns to allow nullable. ([2ebe8ac](https://github.com/RTippin/messenger/commit/2ebe8ac4774f1f72d124fede7085e0fdff5de536))
  - `EmojiInterface`, `BotMatchingService`, `MessageTransformer`, and `MessengerComposer` now allow null for message body.
- Event subscribers will now always be registered, but conditionally check whether to handle events triggered. (avoids being disabled from config and not enabling dynamically when they were not registered in our service provider). ([692256b](https://github.com/RTippin/messenger/commit/692256ba875db7c92426d945c39093f869f63d6e))

---

### [v0.44 (07-18-2021)]

#### Changed
- Using core php method `is_subclass_of` instead of my custom reflection checks. ([303e12d](https://github.com/RTippin/messenger/commit/303e12d4576635e704598e82ce9cf5a83b1a110e))
- Calls down command now re-sets the down cache lock when triggered while calls already down. ([44b768a](https://github.com/RTippin/messenger/commit/44b768ad94ab64e7490fc5f5ed2c7903963df287))

#### Removed
- `ProvidersCacheCommand` and `ProvidersClearCommand`
- `Searchable` contract.
- `checkImplementsInterface` and `checkIsSubclassOf` from `Helpers` class.
- Commit ([a4866a0](https://github.com/RTippin/messenger/commit/a4866a0115afb4f9ef83dc3530efbc6dd09d1ded))

---

### [v0.43 (07-16-2021)]

### Commits ([329c2d0](https://github.com/RTippin/messenger/commit/329c2d0f3e2f2c72f621594479565ef344c53005), [0b647d0](https://github.com/RTippin/messenger/commit/0b647d0fecaaac0ed808b3cf3a417a161b4b043c))

#### Added
- `getProviderSettings` public static method to the `MessengerProvider` contract.
- `getProviderSettings` default added to the `Messageable` trait.
- `MessengerServiceProvider` stub we now publish to the end users project. We also insert the provider to the `app.php` providers array.

#### Changed
- Messenger `getAllMessengerProviders` renamed to `getAllProviders`.
- Messenger `setMessengerProviders` renamed to `registerProviders`.
- MessengerBots `setHandlers` renamed to `registerHandlers`. `setHandlers` kept but deprecated.
- Messenger `getConfig` no longer returns providers.
- Provider interactions reversed. Now you set the classes you do not want the provider to interact with.
- `messenger:publish` command renamed to `messenger:install`.

### Removed
- `providers` array from config.
- `getFriendableForCurrentProvider` from Messenger.
- `ProvidersCacheCommand` and `ProvidersClearCommand` deprecated. Providers caching removed from MessengerConfig.
- `Searchable` contract deprecated.
- `ProvidersVerification.php` class.

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
