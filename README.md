# Laravel Messenger

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Tests][ico-test]][link-test]
[![StyleCI][ico-styleci]][link-styleci]
[![License][ico-license]][link-license]

---
![Preview](docs/images/image1.png?raw=true)
---

---

### Prerequisites
- PHP >= 7.4 | 8.0
- Laravel >= 8.42
- laravel broadcast driver configured, and your own websocket implementation.
- `SubstituteBindings::class` route model binding enabled in your API / WEB middleware groups.

### Features
- Realtime messaging between multiple models, such as a User, Admin, and Teacher model.
- RESTful API, allowing you to make your own UI or connect to a mobile app.
- Support for morph maps on your provider models. See: [Morph Maps][link-morph-maps]
- Private and group threads.
- Permissions per participant within a group thread.
- Send image, document or audio messages.
- Message reactions, replies, edits, and deletion.
- Group thread chat-bots. [Bot addon][link-messenger-bots]
- Friends, Search, and Online status systems.
- Provider, group thread, and bot avatars.
- Underlying calling system you can extend.
- Group thread invitation links (like discord).
- All actions are protected behind policies.
- Scheduled commands for automation cleanup and checks.
- Queued jobs fired from our event subscribers.
- Most features can be toggled at runtime using our `Messenger` facade.
- Optional extra payload when sending messages to allow custom json to be stored with the message.

### Upcoming for v2
- Temporary Ephemeral conversations.
- Improved API pagination / filters.
- Possible extraction of friends system.
- Better image manipulation / saving of multiple sizes.
- Encryption of messages (E2E is the long term goal).

### Notes
- New docs are being worked on.
- Read through our [`messenger.php`][link-config] config file before migrating!
- Calling is disabled by default. You are responsible for implementing your own media server or connecting to a 3rd party service.

---

# Installation

### Composer

``` bash
$ composer require rtippin/messenger
```

### To complete our setup, please refer to the installation docs listed below:

---

# Documentation

- ### [Install / Registering Providers][link-installation]
- ### [Configuration][link-configuration]
- ### [Commands][link-commands]
- ### [Broadcasting][link-broadcasting]
- ### [Chat Bots][link-chat-bots]
- ### [Calling][link-calling]
- ### [Helpers / Facades][link-helpers]
- ### [API Explorer][link-api-explorer]

---

## Addons / Demo

- [Messenger Bots][link-messenger-bots] - Bot functionality is built into the core of this `MESSENGER` package, but you are responsible for registering your own bot handlers.
- [Messenger Faker][link-messenger-faker] - An addon package useful in dev environments to mock/seed realtime events and messages.
- [Messenger Web UI][link-messenger-ui] - Addon package containing ready-made web routes and publishable views / assets, including default images.
- [Demo App][link-demo-source] - You may view our demo laravel 8 source with this package installed, including a [Live Demo][link-live-demo].

---

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
[link-calling]: docs/Calling.md
[link-chat-bots]: docs/ChatBots.md
[link-helpers]: docs/Helpers.md