# Installation

---

### Composer

``` bash
$ composer require rtippin/messenger
```

### Install Command
***This will publish our config and service provider files. The service provider will also be registered in your `app.php` config file.***
```bash
$ php artisan messenger:install
```

### Before Migrating
***Check out the published [`messenger.php`][link-config] config file in your config directory. You must first specify if you plan to use UUIDs on your provider models before running the migrations. (False by default)***
```php
'provider_uuids' => false,
```
### Migrate
```bash
$ php artisan migrate
```

---

# Register Providers

- Head over to your new `App\Providers\MessengerServiceProvider`
- Set all provider models you want to register into messenger. The default `App\Models\User` is already preset, you just need to un-comment it.

**Default:**

```php
<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Facades\Messenger;

class MessengerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Messenger::registerProviders([
            User::class,
        ]);
    }
}
```

---

### Implement our MessengerProvider contract for each provider registered

- Each provider you define will need to implement our [`MessengerProvider`][link-messenger-contract] contract. We include a [`Messageable`][link-messageable] trait you can use on your providers that will usually suffice for your needs. This trait has all the methods needed to satisfy the contract.
- You will typically want to override our `getProviderSettings()` method per provider you register.
- The `alias` will be auto-generated if null or not set. When auto-generating, we will use the lower-snake case of the model's name.

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
    
    public static function getProviderSettings(): array
    {
        return [
            'alias' => null, // If set, will overwrite auto-generating alias
            'searchable' => true,
            'friendable' => true,
            'devices' => true,
            'default_avatar' => public_path('vendor/messenger/images/users.png'),
            'cant_message_first' => [],
            'cant_search' => [],
            'cant_friend' => [],
        ];
    }
}
```

---

### Searchable

- You must implement the `getProviderSearchableBuilder` on providers you want to be searchable. We also include a [`Search`][link-search] trait that works out of the box with the default laravel User model.
    - You must also ensure `searchable` in the providers `getProviderSettings` method is true (default).
- If you have different columns used to search for your provider, you can skip using the default `Search` trait, and define the public static method yourself.
    - We inject the query builder, along with the original full string search term, and an array of the search term exploded via spaces and commas.

***Example:***

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Traits\Messageable;
use RTippin\Messenger\Traits\Search;

class User extends Authenticatable implements MessengerProvider
{
    use Messageable, 
        Search; // Use this default trait or set your own like below
        
  public static function getProviderSearchableBuilder(Builder $query,
                                                      string $search,
                                                      array $searchItems)
  {
      $query->where(function (Builder $query) use ($searchItems) {
          foreach ($searchItems as $item) {
              $query->orWhere('first_name', 'LIKE', "%{$item}%")
              ->orWhere('last_name', 'LIKE', "%{$item}%");
          }
      })->orWhere('email', '=', $search);
  }
}
```

---

### Friendable

- Allows your provider to be friended / have friends. We currently include a friends system and migrations (this will be extracted out of this package in a future release).
- Set `friendable` in the providers `getProviderSettings` method to true (default).

---

### Devices

- Devices are a helpful way for you to attach a listener onto our [PushNotificationEvent][link-push-event]. When any broadcast over a private channel occurs, we forward a stripped down list of all recipients/providers and their types/IDs, along with the original data broadcasted over websockets, and the event name.
    - To use this default event, you must be using our `default` broadcast driver, and have `push_notifications` enabled. How you use the data from our event to send push notifications (FCM etc) is up to you!

---

### Provider Interactions `cant_message_first` `cant_search` `cant_friend`

- Provider interactions give fine grain control over how your provider can interact with other providers, should you have multiple.
- For each interaction, list the provider classes you want to deny that action from the parent provider.


`cant_message_first` revokes permissions to initiate a private conversation with the given providers. This does not stop or alter private threads already created, nor does it impact group threads. Initiating a private thread is defined as "messaging first".

***Example: A user may not be able to start a conversation with a company, but a company may be allowed to start the conversation with the user. Once a private thread is created, it is business as usual!***

```php
//User
return [
    'cant_message_first' => [Company::class],
];

//Company
return [
    'cant_message_first' => [], //no restrictions
];
```

`cant_search` Filters search results, omitting the listed providers.

***Example: A user may not be allowed to search for companies, but a company can search for users.***

```php
//User
return [
    'cant_search' => [Company::class],
];

//Company
return [
    'cant_search' => [], //no restrictions
];
```

`cant_friend` Revokes permission to initiate a friend request with the listed providers. This permission only impacts when one provider sends another a friend request. Cancelling / Accepting / Denying a friend request, or your list of actual friends, is not impacted by this permission.

***Example: A user may not be allowed to send a friend request to a company, but a company can send a friend request to a user.***

```php
//User
return [
    'cant_friend' => [Company::class],
];

//Company
return [
    'cant_friend' => [], //no restrictions
];
```

---

### Providers name

- To grab your providers name, our default returns the 'name' column from your model, stripping tags and making words uppercase. You may overwrite the way the name on your model is returned using the below method.

***Example:***

```php
public function getProviderName(): string
{
    return strip_tags(ucwords($this->first." ".$this->last));
}
```

---

### Providers avatar column

- When provider avatar upload/removal is enabled, we use the default `string/nullable` : `picture` column on that provider models table.
    - You may overwrite the column name on your model using the below method, should your column be named differently.

***Example:***

```php
public function getProviderAvatarColumn(): string
{
    return 'avatar';
}
```

---

### Providers last active column

- When online status is enabled, we use the default `timestamp` : `updated_at` column on that provider models table. This is used to show when a provider was last active, and is the column we will update when you use the messenger status heartbeat.
    - You may overwrite the column name on your model using the below method, should your column be named differently.

***Example:***

```php
    public function getProviderLastActiveColumn(): string
    {
        return 'last_active';
    }
```

[link-config]: https://github.com/RTippin/messenger/blob/1.x/config/messenger.php
[link-messageable]: https://github.com/RTippin/messenger/blob/1.x/src/Traits/Messageable.php
[link-search]: https://github.com/RTippin/messenger/blob/1.x/src/Traits/Search.php
[link-messenger-contract]: https://github.com/RTippin/messenger/blob/1.x/src/Contracts/MessengerProvider.php
[link-push-event]: https://github.com/RTippin/messenger/blob/1.x/src/Events/PushNotificationEvent.php