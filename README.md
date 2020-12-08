# Messenger

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

# Laravel 8 Messenger suite

### Prerequisites
- PHP >= 7.4
- Laravel >= 8.x

## Installation

Via Composer

``` bash
$ composer require rtippin/messenger
```

Publish package assets / configs / views

```bash
$ php artisan messenger:publish
```

Check out the published `messenger.php` config file in your config/ directory. You are going to want to first specify if you plan to use UUIDs on your provider models before running the migrations.

```bash
$ php artisan migrate
```

Add every provider model you wish to use within the providers array in our config. Each provider will need to implement our `MessengerProvider` contract. We include a Messageable trait you can use on your providers that will usually suffice for your needs.

If you want your provider to be searchable, you must implement our `Searchable` contract on those providers. We also include a Search trait that works out of the box with the default laravel User model.

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## Credits

- [Richard Tippin][link-author]

## License

license. Please see the [license file](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/rtippin/messenger.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/rtippin/messenger.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/rtippin/messenger/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/309521487/shield

[link-packagist]: https://packagist.org/packages/rtippin/messenger
[link-downloads]: https://packagist.org/packages/rtippin/messenger
[link-travis]: https://travis-ci.org/rtippin/messenger
[link-styleci]: https://styleci.io/repos/309521487
[link-author]: https://github.com/rtippin
