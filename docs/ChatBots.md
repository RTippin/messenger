# Chat Bots

---

***Default Config:***

```php
'bots' => [
    'enabled' => env('MESSENGER_BOTS_ENABLED', false),
    'subscriber' => [
        'enabled' => true,
        'queued' => true,
        'channel' => 'messenger-bots',
    ],
],
```

- Bots are disabled by default. When enabled, bots may be created within group threads. A bot may contain many actions with many triggers that will respond to a message.
- We provide an event subscriber ([BotSubscriber][link-bot-subscriber]) to listen and react to events that may trigger a bot response. You may choose to enable it, whether it puts jobs on the queue or not, and which queue channel its jobs are dispatched on.
- To use our pre-made bot handlers, please install our [Messenger Bots][link-messenger-bots] package.

---

## Bots System

### Automation

**Based on the configs set above, you will want your queue worker listening for the `messenger-bots` queue to handle bot related jobs.**
```bash
php artisan queue:work --queue=messenger-bots
```
**To automate purging archived bots and their files from storage, you may schedule our command at whichever interval matches your needs within your `Kernel`**
```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('messenger:purge:bots')->dailyAt('6:00');
    }
}
```

### General Flow

- You will register your custom bot handlers (more on that below).
- A bot can be created on a group thread with bots enabled.
- The bot can then have actions attached, where the actions have triggers and a handler that will be resolved when a match is found.
- When a message is sent, we fire our `NewMessageEvent`.
- Our [BotSubscriber][link-bot-subscriber] will listen for the `NewMessageEvent`, and dispatch the `BotActionMessageHandler` job.
  - This job will only be dispatched if the message sent is from a group thread, not a system message, not from a bot, is a text based message, and its thread has bots enabled.
- `BotActionMessageHandler` will process each active bot on the messages thread, loading all attached actions on the bots, and looping through them to match any triggers against the message sent.
- When a trigger is matched against the message, we will instantiate that actions [BotActionHandler][link-action-handler] and execute its `handle` method.

---

## Creating Bot Handlers

**Create your handler class and extend our [BotActionHandler][link-action-handler] abstract class.**

- At the very minimum, your class must define a public `handle()` method and a public static `getSettings()` method.
- Should you need to inject dependencies, you may add your own constructor and type hint any dependencies. Your handler class will be instantiated using laravels container.

**Example**
```php
<?php

namespace App\Bots;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class TestBot extends BotActionHandler
{
    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'testing',
            'description' => 'I am a test bot handler.',
            'name' => 'McTesting!',
            'unique' => true,
            'match' => 'exact',
            'triggers' => ['!test', '!trigger'],
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        //Do some magic!
    }
}
```

---

### `getSettings()`

**Must return an array defining the handler's `alias`, `description`, and `name`.**

**`unique`, `match`, and `triggers` are optional overrides.**

- `alias` Used to locate and attach your handler to a bot.
- `description` The description of your bot handler, typically what it does.
- `name` The name of your bot handler.
- `unique` (optional) When set and true, the handler may only be used once per bot.
- `triggers` (optional) Overrides allowing the end user to set the triggers. Only the given trigger(s) will be used. Separate multiple via the pipe (|) or use an array.
- `match` (optional) Overrides allowing end user to select matching method.

---

#### Available match methods
- `contains` - The trigger can be anywhere within a message. Cannot be part of or inside another word.
- `contains:caseless` - Same as "contains", but is case insensitive.
- `contains:any` - The trigger can be anywhere within a message, including inside another word.
- `contains:any:caseless` - Same as "contains any", but is case insensitive.
- `exact` - The trigger must match the message exactly.
- `exact:caseless` - Same as "exact", but is case insensitive.
- `starts:with` - The trigger must be the lead phrase within the message. Cannot be part of or inside another word.
- `starts:with:caseless` - Same as "starts with", but is case insensitive.

---

### `handle()`
- The handle method will be executed when a matching actions trigger is associated with your bot handler.
- When your handle method is called, you will have access to many properties already set by the messenger core.
    - `$this->action` provides the current `BotAction` model that was matched to your handler.
    - `$this->thread` provides the group `Thread` model we are using.
    - `$this->message` provides the `Message` model we are using. You can also access the message sender via the owner relation `$this->message->owner`.
    - `$this->matchingTrigger` provides the trigger that was matched to the message.
    - `$this->isGroupAdmin` boolean for if the message sender is a group admin or not.
    - `$this->senderIp` provides the IP of the message sender.

### `getPayload(?string $key = null)`
- If your handler stores extra data as a `payload`, it will be stored as JSON.
- getPayload will return the decoded array, with an optional `$key` to return a specific value.

### `releaseCooldown()`
- Calling this will remove any cooldown set on the `BotAction` model after your handle method is executed.
- Cooldowns are optional and are set by the end user, per action. A cooldown will be started right before your handle method is executed.
- This is helpful when your handler did not perform an action (perhaps an API call that was denied), and you can ensure any cooldowns defined on that bot action are removed.

### `composer()`
- Returns a [MessengerComposer][link-messenger-composer] instance with the `TO` preset with the messages thread, and `FROM` preset as the bot the action belongs to.
    - Please note that each time you call `$this->composer()`, you will be given a new instance.
- This has the most common use cases for what a bot may do (message, send an image/audio/document message, add message reaction, knock)
    - `silent()` Silences any realtime broadcast.
    - `message()` Sends a message. Optional reply to ID and extra payload.
    - `image()` Uploads an image message. Optional reply to ID and extra payload.
    - `document()` Uploads a document message. Optional reply to ID and extra payload.
    - `audio()` Uploads an audio message. Optional reply to ID and extra payload.
    - `reaction()` Adds a reaction to the message.
    - `knock()` Sends a knock to the current thread.
    - `read()` Marks the thread read for the `FROM` or set participant.
    - `emitTyping()` Emits a typing presence client event. (Bot typing).
    - `emitStopTyping()` Emits a stopped typing presence client event. (Bot stopped typing).
    - `emitRead` Emits a read presence client event. (Bot read message).

---

#### Custom payloads
- To allow your handler to store user generated data for later use, you must define the validation rules we will use when the end user is attaching your handler to a bot.
- All fields you define in your rules will be serialized and stored as json on the `BotAction` model your handler gets attached to.
- The rules and optional error message overrides use laravels validator under the hood, just how a form request class implements them.

---

### `rules()`
- Return the validation rules used when adding the action to a bot. Any rules you define will have their keys/values stored in the actions payload. Return an empty array if you have no extra data to validate or store.
 
###`errorMessages()`
- If you define extra validation rules, you may also define the validator error messages here.

### `serializePayload(?array $payload)`
- This method will be called when storing your handler data to the database. By default, this method `json_encode()` your rule keys and their data.
- You may overwrite this method if you plan to do further sanitization/manipulation of data before it is stored.

---

**Example handler that sends a message and adds a reaction when triggered.**
```php
    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->composer()->emitTyping()->message('I can send messages and react!');

        $this->composer()->reaction($this->message, '💯');
    }
```

**Example reply bot, allowing end user to store up to 5 replies to the handler.**
```php
<?php

namespace App\Bots;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class ReplyBot extends BotActionHandler
{
    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'reply',
            'description' => 'Reply with the given response(s).',
            'name' => 'Reply',
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'replies' => ['required', 'array', 'min:1', 'max:5'],
            'replies.*' => ['required', 'string'],
        ];
    }

    /**
     * @return array
     */
    public function errorMessages(): array
    {
        return [
            'replies.*.required' => 'Reply is required.',
            'replies.*.string' => 'A reply must be a string.',
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->composer()->emitTyping();

        foreach ($this->getPayload('replies') as $reply) {
            $this->composer()->message($reply);
        }
    }
}
```

---

### Authorization
- To authorize the end user add the action handler to a bot, you must define the 'authorize()' method and return true or false. 
  - If the end user is unauthorized, the handler will be hidden from appearing in the available handlers list while adding actions to a bot. This does NOT authorize being triggered once added to a bot action.
```php
<?php

namespace App\Bots;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class TestBot extends BotActionHandler
{
    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'testing',
            'description' => 'I am a test bot handler.',
            'name' => 'McTesting!',
            'unique' => true,
            'match' => 'exact',
            'triggers' => ['!test', '!trigger'],
        ];
    }
    
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->composer()->emitTyping()->message('I need authorization to be added to a bot!');
    }
}
```

---

## Register your Handlers
- Once you are ready to make your handler available for use, head to your `MessengerServiceProvider` and add your handler classes to the `MessengerBots::registerHandlers()` method.
```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Facades\MessengerBots;
use App\Bots\ReplyBot;
use App\Bots\TestBot;


class MessengerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        MessengerBots::registerHandlers([
            ReplyBot::class,
            TestBot::class,
        ]);
    }
}
```

[link-messenger-bots]: https://github.com/RTippin/messenger-bots
[link-bot-subscriber]: ../src/Listeners/BotSubscriber.php
[link-action-handler]: ../src/Actions/Bots/BotActionHandler.php
[link-action-interface]: ../src/Contracts/ActionHandler.php
[link-messenger-composer]: ../src/Support/MessengerComposer.php