# Helpers and Facades

---

## [Messenger][link-messenger]

- The core class of messenger, and holds most of the configuration getters and setters.
- This is a `singleton` and will only be loaded once into the container per request cycle.

```php
//Using the container.
$config = app(\RTippin\Messenger\Messenger::class)->setProvider();

//Using the alias.
$config = app('messenger')->setProvider();

//Using the facade.
$config = \RTippin\Messenger\Facades\Messenger::getConfig();

//Using the helper.
$config = messenger()->getConfig();
```

---

## [MessengerBots][link-bots]

- The core Bots class that manages registered bot handlers and bot validations.
- This is a `singleton` and will only be loaded once into the container per request cycle.

```php
//Using the container.
$handlers = app(\RTippin\Messenger\MessengerBots::class)->getHandlerClasses();

//Using the alias.
$handlers = app('messenger-bots')->getHandlerClasses();

//Using the facade.
$handlers = \RTippin\Messenger\Facades\MessengerBots::getHandlerClasses();

//Using the helper.
$handlers = bots()->getHandlerClasses();
```

---

## [MessengerComposer][link-composer]

- This support class allows you to send messages / reactions / events and more to a given thread or between two providers.
- This is not a `singleton`, and you will be given a new class instance each time you call to one of our helpers.

```php
//Using the container.
app(\RTippin\Messenger\Support\MessengerComposer::class)->to($receiver)
    ->from($sender)
    ->emitTyping()
    ->message('Hello!');

//Using the alias.
app('messenger-composer')->to($receiver)
    ->from($sender)
    ->emitTyping()
    ->message('Hello!');

//Using the facade.
\RTippin\Messenger\Facades\MessengerComposer::to($receiver)
    ->from($sender)
    ->emitTyping()
    ->message('Hello!');

//Using the helper.
messengerComposer()->to($receiver)
    ->from($sender)
    ->emitTyping()
    ->message('Hello!');
```

[link-messenger]: https://github.com/RTippin/messenger/blob/1.x/src/Messenger.php
[link-bots]: https://github.com/RTippin/messenger/blob/1.x/src/MessengerBots.php
[link-composer]: https://github.com/RTippin/messenger/blob/1.x/src/Support/MessengerComposer.php