# Helpers and Facades

---

## [Messenger][link-messenger]

- This is our core class, and holds most of the configuration getters and setters.
- This is a `singleton` and will only be loaded once into the container per request cycle.

```php
//Using the container / dependency injection.
$config = app(\RTippin\Messenger\Messenger::class)->setProvider();

//Using the facade.
$config = \RTippin\Messenger\Facades\Messenger::getConfig();

//Using the helper.
$config = messenger()->getConfig();
```

---

## [MessengerBots][link-bots]

- This is our core Bots class that manages registered bot handlers and bot validations.
- This is a `singleton` and will only be loaded once into the container per request cycle.

```php
//Using the container / dependency injection.
$handlers = app(\RTippin\Messenger\MessengerBots::class)->getHandlerClasses();

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
//Using the container / dependency injection.
app(\RTippin\Messenger\Support\MessengerComposer::class)->to($receiver)
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

[link-messenger]: ../src/Messenger.php
[link-bots]: ../src/MessengerBots.php
[link-composer]: ../src/Support/MessengerComposer.php