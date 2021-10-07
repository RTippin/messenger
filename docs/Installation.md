# Installation

---

### Composer

```bash
composer require rtippin/messenger
```

### Install Command
- Installs the base messenger files, publishing our [`messenger.php`][link-config] config and service provider.
- This will also register our published service provider in your `app.php` config file under the providers array.
- You will be asked to confirm running this command, as well as an option to migrate our tables before completion.
```bash
php artisan messenger:install
```

- If your provider models use UUIDs instead of auto-incrementing integers as their primary keys, use the `--uuids` flag when installing.
```bash
php artisan messenger:install --uuids
```

### Migrate
- If you opted to not migrate while using the `messenger:install` command above, be sure you run it yourself before using our system.
```bash
php artisan migrate
````

### Follow the instructions below for setting up your providers

---

## Messenger Providers

- Providers are the model's from your application that you want to incorporate into `Messenger`, giving each model their own "inbox" and allowing participation in our messages/threads.
- For most applications, you will only register your `User` model. However, if you had a `User` and a `Teacher` model, and users with permission can view your teacher's portal, you can register both models with `Messenger`, allowing teachers to have their own inbox, and being able to message users as a teacher.
- By default, our `Bot` model is a registered internally as a provider, allowing it to participate in group threads.
- Your provider models will also use our internal [Messenger.php][link-messenger-model] model, which acts as a settings model, as well as allowing reverse search. More on this below, after registering providers.

---

### Registering Providers

- Head over to your new `App\Providers\MessengerServiceProvider`
- Using our `Messenger` facade, set all provider models you want to register into `Messenger`. The default `App\Models\User` is already preset, you just need to un-comment it.

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

### Implement our `MessengerProvider` interface for each provider registered

- Each provider you define will need to implement our [`MessengerProvider`][link-messenger-contract] contract. We include a [`Messageable`][link-messageable] trait you can use on your providers that will usually suffice for your needs. This trait has all the methods needed to satisfy the contract.
- You should override our `getProviderSettings()` method on each provider model you register.
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
    use Messageable; //Default trait to satisfy MessengerProvider interface
    
    public static function getProviderSettings(): array
    {
        return [
            'alias' => null, // Auto-generated unless defined
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
  - To use this default event, you must be using our `default` broadcast driver, and have `push_notifications` enabled. How you use the data from our event to send push notifications (FCM, APN, etc.) is up to you!

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

- If your provider (Eg: `User`) does not have any "avatar/picture" column, or you wish to add a new custom one for use with messenger only, you may create a custom migration to add your new column (must be `nullable`).

***Example Migration and new avatar column override for `User`:***

```php
//Your migration
Schema::table('users', function (Blueprint $table) {
    $table->string('messenger_avatar')->nullable();
});

//User model override
public function getProviderAvatarColumn(): string
{
    return 'messenger_avatar';
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

---

## Messenger Model
- Our [Messenger.php][link-messenger-model] model allows your providers to have individual "settings", such as online status and notification sound toggles.
- We also use a `whereHasMorph` query through our `messengers` table, letting your providers search for others through our API.
- By default, the `Messenger` model will be created if it does not exist for a provider using our API. However, you should attach this model yourself anytime one of your providers is created.

---

### Attaching the model to your existing records
- If you are installing `Messenger` into an application with existing providers/users, you can use our command below to attach the [Messenger.php][link-messenger-model] model to all existing records for each of the providers you registered above.

```bash
php artisan messenger:attach:messengers
```

#### See our [Command's][link-commands-docs] documentation for more information.

---

### Attaching the model on creation
- When one of your registered providers is created, such as a new `User`, you should attach our [Messenger.php][link-messenger-model] model using one of the methods below:

#### Using the getter on our facade, we perform a `firstOrCreate` for the messenger model
***Example using a User model***
```php
use App\Models\User;
use RTippin\Messenger\Facades\Messenger;

$user = User::create([
    'email' => 'new@example.org'
]);

Messenger::getProviderMessenger($user);
```

#### Using our factory to generate the model directly
***Example using a User model***
```php
use App\Models\User;
use RTippin\Messenger\Models\Messenger;

$user = User::create([
    'email' => 'new@example.org'
]);

Messenger::factory()->owner($user)->create();
```

#### For your model factories, you can implement the `configure` `afterCreating` method to attach the messenger model
***Example using a User model factory***
```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Models\Messenger;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'demo' => true,
            'admin' => false,
            'password' => 'password',
        ];
    }

    public function configure(): self
    {
        return $this->afterCreating(function (User $user) {
            Messenger::factory()->owner($user)->create();
        });
    }
}

```

[link-config]: https://github.com/RTippin/messenger/blob/1.x/config/messenger.php
[link-messageable]: https://github.com/RTippin/messenger/blob/1.x/src/Traits/Messageable.php
[link-search]: https://github.com/RTippin/messenger/blob/1.x/src/Traits/Search.php
[link-messenger-contract]: https://github.com/RTippin/messenger/blob/1.x/src/Contracts/MessengerProvider.php
[link-push-event]: https://github.com/RTippin/messenger/blob/1.x/src/Events/PushNotificationEvent.php
[link-messenger-model]: https://github.com/RTippin/messenger/blob/1.x/src/Models/Messenger.php
[link-commands-docs]: Commands.md