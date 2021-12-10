# Laravel Messenger

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Tests][ico-test]][link-test]
[![StyleCI][ico-styleci]][link-styleci]
[![License][ico-license]][link-license]

---
![Preview](https://raw.githubusercontent.com/RTippin/messenger/1.x/docs/images/image1.png?raw=true)
---

---

### Prerequisites
- PHP `^7.4 | ^8.0 | ^8.1`
- Laravel `^8.42`
- `SubstituteBindings::class` route model binding enabled in your API / WEB middleware groups.
- Configure your laravel applications broadcast driver and set up your websocket implementation to utilize the real-time broadcast emitted.

### Features
- Realtime messaging between multiple models, such as a User, Admin, and a Teacher model.
- RESTful API, allowing you to create your own UI or connect to your mobile app.
- Private and group threads.
- Message reactions, replies, edits, and deletion.
- Send image, document, audio, and video messages.
- Group thread chat-bots. [Ready-made bots][link-messenger-bots]
- Customize and create your own chat-bot handlers and bot packages. See the [Chat Bots][link-chat-bots] documentation.
- Private thread approval when the two participants are not friends.
- Add participants in a group thread from your friends list.
- Permissions per participant within a group thread.
- Friends, Search, and Online status systems.
- Provider avatars, group thread avatars, and bot avatars.
- Underlying calling system you can extend. See the [Calling][link-calling] documentation.
- Group thread invitation links (like discord).
- All actions are protected behind policies.
- Scheduled commands for automated cleanup and checks.
- Queued jobs fired from our event subscribers.
- Most features can be toggled at runtime using our `Messenger` facade.
- `MessengerComposer` facade allows you to have easy access to our core actions anywhere within your own app, such as sending messages, images, reactions, and more.
- You can implement or extend your own `BroadcastDriver`, `VideoDriver`, and `FriendDriver`, simply by binding your classes into the container.
- Support for morph maps on your provider models. See: [Morph Maps][link-morph-maps]
- Optional extra payload when sending messages to allow custom json to be stored with the message.
- Owner relationships returns a `Ghost Profile` when not found (null-object pattern).
- Private threads auto-lock when the recipient is not found (deleted).

### Upcoming for v2
- Temporary Ephemeral conversations.
- Improved API pagination / filters.
- Condense attachment routes.
- Possible extraction of friends system.
- Improved image manipulation / saving of multiple sizes.
- Encryption of messages (E2E is the long term goal).
- Translations for internal messages.
- Pinned messages.
- Chat-bots able to trigger off of an event.

### Notes
- Read through the [`messenger.php`][link-config] config file before migrating!
- This is a pure backend driven package providing an API to interact with, thus no web UI or websocket implementation will be setup for you.
- Calling is disabled by default. You are responsible for implementing your own media server or connecting to a 3rd party provider.

---

# Installation

### Composer

```bash
composer require rtippin/messenger
```

### To complete the setup, please refer to the installation documentation listed below:

---

# Documentation

- ### [Install / Registering Providers][link-installation]
- ### [Configuration][link-configuration]
- ### [Commands][link-commands]
- ### [Broadcasting][link-broadcasting]
- ### [Events][link-events]
- ### [Chat Bots][link-chat-bots]
- ### [Calling][link-calling]
- ### [Messenger Composer][link-messenger-composer]
- ### [Helpers / Facades][link-helpers]
- ### [API Explorer][link-api-explorer]

---

## Addons / Demo

- [Messenger Bots][link-messenger-bots] - Pre-made bots you can register within this package.
- [Messenger Faker][link-messenger-faker] - Adds commands useful in development environments to mock/seed realtime events and messages.
- [Messenger Web UI][link-messenger-ui] - Ready-made web routes and publishable views / assets, including default images.
- [Demo App][link-demo-source] - A demo laravel app with this core package installed, including a [Live Demo][link-live-demo].

---

## Credits - [Richard Tippin][link-author]

### [LICENSE][link-license]

### [CHANGELOG][link-changelog]

## Security

If you discover any security related issues, please email author instead of using the issue tracker.

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
[link-config]: https://github.com/RTippin/messenger/blob/1.x/config/messenger.php
[link-api-explorer]: https://tippindev.com/api-explorer
[link-morph-maps]: https://laravel.com/docs/8.x/eloquent-relationships#custom-polymorphic-types
[link-messenger-bots]: https://github.com/RTippin/messenger-bots
[link-messenger-faker]: https://github.com/RTippin/messenger-faker
[link-messenger-ui]: https://github.com/RTippin/messenger-ui
[link-demo-source]: https://github.com/RTippin/messenger-demo
[link-live-demo]: https://tippindev.com
[link-installation]: docs/Installation.md
[link-configuration]: docs/Configuration.md
[link-commands]: docs/Commands.md
[link-broadcasting]: docs/Broadcasting.md
[link-events]: docs/Events.md
[link-calling]: docs/Calling.md
[link-chat-bots]: docs/ChatBots.md
[link-helpers]: docs/Helpers.md
[link-messenger-composer]: docs/Composer.md
[link-changelog]: https://github.com/RTippin/messenger/blob/1.x/CHANGELOG.md
[link-license]: https://github.com/RTippin/messenger/blob/1.x/LICENSE.md