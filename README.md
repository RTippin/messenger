# Laravel 8 Messenger

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![StyleCI][ico-styleci]][link-styleci]
[![License][ico-license]][link-license]

---

<img src="https://i.imgur.com/lnsRJfV.png" style="width:100%;"  alt="Demo"/>

---

### Prerequisites
- PHP >= 7.4
- Laravel >= 8.x
- laravel broadcast driver configured.

### Notes / upcoming
- If our event listeners are enabled in your config, the queue your worker must use is `messenger`, as all listeners are queued on that channel.
- Our included commands that push a job also use the `messenger` queue channel.
- If you enable calling, we support an included [Janus Media Server][link-janus-server] driver, which you will still need to install the media server yourself.
- To configure your own 3rd party video provider, checkout our VideoDriver you will need to implement with your own video implementation, and add to our configs [`drivers`][link-config-drivers] section. Then you set the calling driver to your new implementation from our configs [`calling`][link-config-calling] section.
- A React frontend will be in the works.
- Included frontend uses socket.io / laravel-echo. Future release will expand options.
- Expanded docs.
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

# Installation

## Via Composer

``` bash
$ composer require rtippin/messenger
```

## Publish Assets
- To publish views / config / js assets is one easy command, use:
```bash
$ php artisan messenger:publish
```
- To publish individual assets, use:
```bash
$ php artisan vendor:publish --tag=messenger.config
$ php artisan vendor:publish --tag=messenger.views
$ php artisan vendor:publish --tag=messenger.assets
$ php artisan vendor:publish --tag=messenger.migrations
$ php artisan vendor:publish --tag=messenger.janus.config
```
***All publish commands accept the `--force` flag, which will overwrite existing files if already published!***

***Migrations do not need to be published for them to run. It is recommended to leave those alone!***

## Migrate
***Check out the published [`messenger.php`][link-config] config file in your config/ directory. You are going to want to first specify if you plan to use UUIDs on your provider models before running the migrations. (False by default)***

```bash
$ php artisan migrate
```
## Providers Config
- Add every provider model you wish to use within the providers array in our config.

**Example:**

```php
'providers' => [
    'user' => [
        'model' => App\Models\User::class,
        'searchable' => true,
        'friendable' => true,
        'devices' => false,
        'default_avatar' => public_path('vendor/messenger/images/users.png'),
        'provider_interactions' => [
            'can_message' => true,
            'can_search' => true,
            'can_friend' => true,
        ],
    ],
    'company' => [
        'model' => App\Models\Company::class,
        'searchable' => true,
        'friendable' => true,
        'devices' => false,
        'default_avatar' => public_path('vendor/messenger/images/company.png'),
        'provider_interactions' => [
            'can_message' => true,
            'can_search' => true,
            'can_friend' => true,
        ],
    ],
],
```
- Each provider you define will need to implement our [`MessengerProvider`][link-messenger-contract] contract. We include a [`Messageable`][link-messageable] trait you can use on your providers that will usually suffice for your needs.

***Example:***

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Traits\Messageable;

class User extends Authenticatable implements MessengerProvider
{
    use Messageable;
}
```

- When provider avatar upload/removal is enabled, we assume you have a `picture` column that is `string/nullable` on that providers table. You may overwrite the column name on your model using the below method, should your column be named differently.

***Example:***

```php
public function getAvatarColumn(): string
{
    return 'avatar';
}
```

- To grab your providers name, our default returns the 'name' column from your model, stripping tags and making words uppercase. You may overwrite the way the name on your model is returned using the below method.

***Example:***

```php
public function name(): string
{
    return strip_tags(ucwords($this->first." ".$this->last));
}
```

- If you want a provider to be searchable, you must implement our [`Searchable`][link-searchable] contract on those providers. We also include a [`Search`][link-search] trait that works out of the box with the default laravel User model.

***Example:***

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Searchable;
use RTippin\Messenger\Traits\Messageable;
use RTippin\Messenger\Traits\Search;

class User extends Authenticatable implements MessengerProvider, Searchable
{
    use Messageable;
    use Search;
}
```

- If you have different columns used to search for your provider, you can skip using the default `Search` trait, and define the public static method yourself.

***Example:***

```php
public static function getProviderSearchableBuilder(Builder $query,
                                                    string $search,
                                                    array $searchItems): Builder
{
    return $query->where(function (Builder $query) use ($searchItems) {
        foreach ($searchItems as $item) {
            $query->orWhere('company_name', 'LIKE', "%{$item}%");
        }
    })->orWhere('company_email', '=', $search);
}
```

## Storage Config

***Default:***

```php
'storage' => [
    'avatars' => [
        'disk' => 'public',
        'directory' => 'images',
    ],
    'threads' => [
        'disk' => 'messenger',
        'directory' => 'threads',
    ],
],
```

- The default path used for avatar uploads from your providers is set to the default `public` disk laravel uses in the `filesystem.php` config file. Images would then be saved under `storage_path('app/public/images')`
- The default path used for any uploads belonging to a thread is set to the `messenger` disk, which you will have to create within your `filesystem.php` config, or set to a disk of your choosing. Using the below example, thread files would be located under `storage_path('app/messenger/threads')`

***Example disk in filesystem.php:***
```php
'messenger' => [
    'driver' => 'local',
    'root' => storage_path('app/messenger'),
    'url' => env('APP_URL').'/storage',
],
```

## Routing Config

***Default:***

```php
'routing' => [
    'api' => [
        'domain' => null,
        'prefix' => 'api/messenger',
        'middleware' => ['web', 'auth', 'messenger.provider:required'],
        'invite_api_middleware' => ['web', 'auth.optional', 'messenger.provider'],
    ],
    'web' => [
        'enabled' => true,
        'domain' => null,
        'prefix' => 'messenger',
        'middleware' => ['web', 'auth', 'messenger.provider'],
        'invite_web_middleware' => ['web', 'auth.optional', 'messenger.provider'],
    ],
    'provider_avatar' => [
        'enabled' => true,
        'domain' => null,
        'prefix' => 'images',
        'middleware' => ['web', 'cache.headers:public, max-age=86400;'],
    ],
    'channels' => [
        'enabled' => true,
        'domain' => null,
        'prefix' => 'api',
        'middleware' => ['web', 'auth', 'messenger.provider:required'],
    ],
],
```
- Our API is the core of this package, and are the only routes that cannot be disabled. The api routes also bootstrap all of our policies and controllers for you!
- Web routes provide access to our included frontend/UI should you choose to not craft your own.
- Provider avatar route gives fine grain control of how or to whom you want to display provider avatars to.
- Channels are what we broadcast our realtime data over! The included private channel: `private-{alias}.{id}`. Thread presence channel: `presence-thread.{thread}`. Call presence channel: `presence-call.{call}.thread.{thread}`
- For each section of routes, you may choose your desired endpoint domain, prefix and middleware.
- The default `messenger.provider` middleware is included with this package and simply sets the active messenger provider by grabbing the authed user from `$request->user()`. See [SetMessengerProvider][link-set-provider-middleware] for more information.

---

# Commands

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
[link-config-drivers]: https://github.com/RTippin/messenger/blob/master/config/messenger.php#L221
[link-config-calling]: https://github.com/RTippin/messenger/blob/master/config/messenger.php#L236
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
[link-janus-server]: https://janus.conf.meetecho.com/docs/videoroom.html
[link-set-provider-middleware]: https://github.com/RTippin/messenger/blob/master/src/Http/Middleware/SetMessengerProvider.php