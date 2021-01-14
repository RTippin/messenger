# Messenger

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![StyleCI][ico-styleci]][link-styleci]
[![License][ico-license]][link-license]

# Laravel 8 Messenger suite

---

<img src="https://i.imgur.com/lnsRJfV.png" style="width:100%;"  alt="Demo"/>

---

### Prerequisites
- PHP >= 7.4
- Laravel >= 8.x
- laravel broadcast driver if you want to use realtime.

### Notes / upcoming
- Frontend, should you choose to use the one included, is a bit outdated. We are in the process of making a React UI, and later a Vue one as well.
- Included frontend uses socket.io / laravel-echo-server. Future release will expand options.
- Unit Test are coming.
- Expanded docs.
- If our event listeners are enabled in your config, the queue your worker must use is `messenger`, as all listeners are queued on that channel.
- Our included commands that push a job also use the `messenger` queue channel.
- Read through our config file before migrating!

### Messenger Demo
- You may view our demo laravel 8 source with this package installed, including a live demo: 
  - [Demo Source][link-demo-source]
  - [Live Demo][link-live-demo]
- Demo models for how we integrate them with our contracts:
  - [User Model][link-demo-user]
  - [Company Model][link-demo-company]
- Demo console kernel utilizes our commands to track active calls, purge archived files, etc
  - [Console Kernel][link-demo-kernel]

---

## Installation

Via Composer

``` bash
$ composer require rtippin/messenger
```

Publish package assets / configs / views

```bash
$ php artisan messenger:publish
```

Check out the published [`messenger.php`][link-config] config file in your config/ directory. You are going to want to first specify if you plan to use UUIDs on your provider models before running the migrations.

```bash
$ php artisan migrate
```

Add every provider model you wish to use within the providers array in our config. Each provider will need to implement our [`MessengerProvider`][link-messenger-contract] contract. We include a [`Messageable`][link-messageable] trait you can use on your providers that will usually suffice for your needs.

If you want your provider to be searchable, you must implement our [`Searchable`][link-searchable] contract on those providers. We also include a [`Search`][link-search] trait that works out of the box with the default laravel User model.


If you enable all of our routing, simply login after you setup your providers in our config, and head to the default `/messenger` route!

---

## Commands

- `php artisan messenger:publish` | `--force`
    * Publish our views / js / css / config, with a force option to overwrite if already exist.
- `php artisan messenger:calls:check-activity` | `--now`
    * Check active calls for active participants, end calls with none. Option to run immediately without pushing job to queue.
- `php artisan messenger:invites:check-valid` | `--now`
    * Check active invites for any past expiration or max use cases and invalidate them. Option to run immediately without pushing job to queue.
- `php artisan messenger:providers:cache`
    * Cache the computed provider configs for messenger.
- `php artisan messenger:providers:clear`
    * Clear the cached provider config file.
- `php artisan messenger:purge:documents` | `--now` | `--days=30`
    * We will purge all soft deleted document messages that were archived past the set days (30 default). We run it through our action to remove the file from storage and message from database. Option to run immediately without pushing job to queue.
- `php artisan messenger:purge:images` | `--now` | `--days=30`
    * We will purge all soft deleted image messages that were archived past the set days (30 default). We run it through our action to remove the image from storage and message from database. Option to run immediately without pushing job to queue.
- `php artisan messenger:purge:messages` | `--days=30`
    * We will purge all soft deleted messages that were archived past the set days (30 default). We do not need to fire any additional events or load models into memory, just remove from table, as this is not messages that are documents or images. 
- `php artisan messenger:purge:threads` | `--now` | `--days=30`
    * We will purge all soft deleted threads that were archived past the set days (30 default). We run it through our action to remove the entire thread directory and sub files from storage and the thread from the database. Option to run immediately without pushing job to queue.

---

## API endpoints / examples

- [Threads][link-threads]
- [Participants][link-participants]
- [Messages][link-messages]
- [Document Messages][link-documents]
- [Image Messages][link-images]
- [Messenger][link-messenger]
- [Friends][link-friends]
- [Calls][link-calls]
- [Invites][link-invites]

## Credits - [Richard Tippin][link-author]

## License - MIT

### Please see the [license file](LICENSE.md) for more information.

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

[ico-version]: https://img.shields.io/packagist/v/rtippin/messenger.svg?style=plastic&cacheSeconds=3600
[ico-downloads]: https://img.shields.io/packagist/dt/rtippin/messenger.svg?style=plastic&cacheSeconds=3600
[ico-travis]: https://img.shields.io/travis/rtippin/messenger/master.svg?style=plastic&cacheSeconds=3600
[ico-styleci]: https://styleci.io/repos/309521487/shield?style=plastic&cacheSeconds=3600
[ico-license]: https://img.shields.io/github/license/RTippin/messenger?style=plastic
[link-packagist]: https://packagist.org/packages/rtippin/messenger
[link-downloads]: https://packagist.org/packages/rtippin/messenger
[link-license]: https://packagist.org/packages/rtippin/messenger
[link-travis]: https://travis-ci.org/rtippin/messenger
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
[link-messages]: docs/Messages.md
[link-messenger]: docs/Messenger.md
[link-participants]: docs/Participants.md
[link-threads]: docs/Threads.md
[link-invites]: docs/Invites.md
[link-demo-source]: https://github.com/RTippin/messenger-demo
[link-live-demo]: https://tippindev.com
[link-demo-user]: https://github.com/RTippin/messenger-demo/blob/master/app/Models/User.php
[link-demo-company]: https://github.com/RTippin/messenger-demo/blob/master/app/Models/Company.php
[link-demo-kernel]: https://github.com/RTippin/messenger-demo/blob/master/app/Console/Kernel.php