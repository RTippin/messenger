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

- Bots are disabled by default. When enabled, bots may be created within group threads that explicitly enable the bots feature. A bot may contain many actions, and each action may contain many triggers. Upon a trigger matching a message, the action's handler class will process and respond to the message.
- The included event subscriber ([BotSubscriber][link-bot-subscriber]) will listen and react to events that may trigger a bot response. You may choose to enable it, whether it puts jobs on the queue or not, and which queue channel its jobs are dispatched on.
- Ready-made bot handlers can be used with the optional [Messenger Bots][link-messenger-bots] package.

---

## Bots System

### Automation

**Based on the configs set above, you will want your queue worker listening on the `messenger-bots` queue to handle bot related jobs.**
```bash
php artisan queue:work --queue=messenger-bots
```
**To automate purging archived bots and their files from storage, you should schedule the bot purge command at a sensible interval within your applications `App\Console\Kernel`**
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

---

### General Flow

- Register your custom bot handlers (more on that below).
- Register your custom packaged bot bundles (more on that below).
- A bot can be created in a group thread with bots enabled.
- The bot will have actions attached, where the actions have triggers and a handler that will be resolved when a match is found against message sent.
- When a message is sent, we fire our `NewMessageEvent`.
- The [BotSubscriber][link-bot-subscriber] will listen for the `NewMessageEvent`, and dispatch the `BotActionMessageHandler` job.
  - This job will only be dispatched if the message sent is from a group thread, not a system message, not from a bot, is a text based message, and its thread has bots enabled.
- `BotActionMessageHandler` will process each active bot on the messages thread, loading all attached actions on the bots, and looping through them to match any triggers against the message sent.
- When a trigger is matched against the message, we will instantiate that actions [BotActionHandler][link-action-handler] and execute its `handle` method.

---

### Success and Failure Events

- If a [BotActionHandler][link-action-handler] throws an exception while being handled, it will be caught and dispatched in the [BotActionFailedEvent][link-action-failed-event].
- Upon a [BotActionHandler][link-action-handler] being handled successfully, the [BotActionHandledEvent][link-action-handled-event] will be dispatched.
  - You must attach your own event listeners to these events if you plan to utilize them.
  - Please see the [Events Documentation][link-events-docs] for more information.

---

# Creating Bot Handlers

**Create your handler class and extend the [BotActionHandler][link-action-handler] abstract class.**

**You can use the included command to generate the bot handler class:**

```bash
php artisan messenger:make:bot TestBot
```

- At the very minimum, your bots class must define a `public handle()` method and a `public static getSettings()` method.
- Should you need to inject dependencies, you may add your own constructor and type hint any dependencies. Your handler class will be instantiated using laravel's container.

**Example**

```php
<?php

namespace App\Bots;

use RTippin\Messenger\Support\BotActionHandler;
use RTippin\Messenger\MessengerBots;
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
            'match' => MessengerBots::MATCH_EXACT,
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

- `alias [string]` Used to locate and attach your handler to a bot.
- `description [string]` The description of your bot handler, typically what it does.
- `name [string]` The name of your bot handler.
- `unique (optional) [bool]` When set and true, the handler may only be used once across all bots in a thread.
- `triggers (optional)  [array]` Overrides allowing the end user to set the triggers. Only the given trigger(s) will be used. Use a single array of all trigger words.
- `match (optional) [string]` Overrides allowing end user to select matching method.

---

#### Available match methods
- `any` - The action will be triggered for any message sent.
- `contains` - The trigger can be anywhere within a message. Cannot be part of or inside another word.
- `contains:caseless` - Same as "contains", but is case-insensitive.
- `contains:any` - The trigger can be anywhere within a message, including inside another word.
- `contains:any:caseless` - Same as "contains any", but is case-insensitive.
- `exact` - The trigger must match the message exactly.
- `exact:caseless` - Same as "exact", but is case-insensitive.
- `starts:with` - The trigger must be the lead phrase within the message. Cannot be part of or inside another word.
- `starts:with:caseless` - Same as "starts with", but is case-insensitive.

##### Match method constants
- It is recommended to use the match method constants located on the [MessengerBots][link-bots-service] core class.

```php
use RTippin\Messenger\MessengerBots;

MessengerBots::MATCH_ANY;
MessengerBots::MATCH_CONTAINS;
MessengerBots::MATCH_CONTAINS_CASELESS;
MessengerBots::MATCH_CONTAINS_ANY;
MessengerBots::MATCH_CONTAINS_ANY_CASELESS;
MessengerBots::MATCH_EXACT;
MessengerBots::MATCH_EXACT_CASELESS;
MessengerBots::MATCH_STARTS_WITH;
MessengerBots::MATCH_STARTS_WITH_CASELESS;
```

---

### `handle()`
- The handle method will be executed when a matching actions trigger is associated with your bot handler.
- When your handle method is called, you will have access to many properties already set by the messenger core.
    - Your class will be instantiated using the container, so any dependencies you type-hint in your constructor will be made available when `handle` is called.
    - `$this->action` provides the current `BotAction` model that was matched to your handler.
    - `$this->thread` provides the group `Thread` model we are using.
    - `$this->message` provides the `Message` model we are using. You can also access the message sender via the owner relation `$this->message->owner`.
    - `$this->matchingTrigger` provides the trigger that was matched to the message.
    - `$this->isGroupAdmin` boolean for if the message sender is a group admin or not.
    - `$this->senderIp` provides the IP of the message sender.

### `getPayload(?string $key = null)`
- If your handler stores extra data as a `payload`, it will be stored as JSON.
- getPayload will return the decoded array, with an optional `$key` to return a specific value.

### `getParsedMessage(bool $toLower = false)`
- Returns the message with the trigger removed.

### `getParsedWords(bool $toLower = false)`
- Returns an array of all words in the message with the trigger removed.

### `releaseCooldown()`
- Calling this will remove any cooldown set on the `BotAction` model after your handle method is executed.
- Cooldowns are optional and are set by the end user, per action. A cooldown will be started right before your handle method is executed.
- This is helpful when your handler did not perform an action (perhaps an API call that was denied), and you can ensure any cooldowns defined on that bot action are removed.

### `composer()`
- Returns a [MessengerComposer][link-messenger-composer] instance with the `TO` preset with the `Thread` our triggering `Message` belongs to, and `FROM` preset as the `Bot` the `BotAction` triggered belongs to.
    - Please note that each time you call `$this->composer()`, you will be given a new instance.
- This has the most common use cases for what a bot may do (message, send an image/audio/document message, add message reaction, knock)
    - `silent()` Silences any broadcast emitted.
    - `message()` Sends a message. Optional reply to ID and extra payload.
    - `image()` Uploads an image message. Optional reply to ID and extra payload.
    - `document()` Uploads a document message. Optional reply to ID and extra payload.
    - `audio()` Uploads an audio message. Optional reply to ID and extra payload.
    - `video()` Uploads a video message. Optional reply to ID and extra payload.
    - `reaction()` Adds a reaction to the message.
    - `knock()` Sends a knock to the current thread.
    - `read()` Marks the thread read for the `FROM` or set participant.
    - `emitTyping()` Emits a typing presence client event. (Bot typing).
    - `emitStopTyping()` Emits a stopped typing presence client event. (Bot stopped typing).
    - `emitRead` Emits a read presence client event. (Bot read message).

---

### Custom payloads
- To allow your handler to store user generated data for later use, you must define the validation rules we will use when the end user is attaching your handler to a bot.
- All fields you define in your rules will be serialized and stored as json on the `BotAction` model your handler gets attached to.
- The rules and optional error message overrides use laravel's validator under the hood.

### `rules()`
- Return the validation rules used when adding the action to a bot. Any rules you define will have their keys/values stored in the action's payload. Return an empty array if you have no extra data to validate or store.
 
### `errorMessages()`
- If you define extra validation rules, you may also define the validator error messages here.

### `serializePayload(?array $payload)`
- This method will be called when the end user adds their custom payload while attaching the action to a bot.
- We will store the validated data posted based on your `rules` defined. By default, this method will `json_encode()` your data.
- You may overwrite this method if you plan to do further sanitization/manipulation of data before it is stored.

---

**Example handler using preset triggers and match method, that sends a welcome message and adds a reaction when triggered.**

```php
<?php

namespace App\Bots;

use RTippin\Messenger\Support\BotActionHandler;
use RTippin\Messenger\MessengerBots;
use Throwable;

class HelloBot extends BotActionHandler
{
    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'hello',
            'description' => 'Say hello when someone says hi!',
            'name' => 'Hello Response',
            'triggers' => ['hello', 'hi', 'hey'],
            'match' => MessengerBots::MATCH_CONTAINS_CASELESS,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->composer()->emitTyping()->message("Why hello there {$this->message->owner->getProviderName()}!");

        $this->composer()->reaction($this->message, '👋');
    }
}
```

**Example reply bot, allowing end user to store up to 5 replies to the handler.**
```php
<?php

namespace App\Bots;

use RTippin\Messenger\Support\BotActionHandler;
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

### Handler Authorization
- To authorize the end user to add your `BotActionHandler` to a bot, you should define the `public authorize()` method and return boolean.
- This method will be called during the http request cycle, giving you access to the current auth/session/etc.
- If the end user is unauthorized, the handler will be hidden from appearing in the available handlers list while adding actions to a bot. 
- The handler will also be authorized while being part of a `PackagedBot` bundle. If a handler is unauthorized, it will be omitted from appearing under a `PackagedBot`'s install list as well as ignored during the `PackagedBot`'s install.
- This does NOT authorize being triggered once added to a bot action.
- If you want to disable authorization for a single request cycle, you can use the facade call: `MessengerBots::shouldAuthorize(false)`

```php
<?php

namespace App\Bots;

use RTippin\Messenger\Support\BotActionHandler;
use RTippin\Messenger\MessengerBots;
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
            'match' => MessengerBots::MATCH_EXACT,
            'triggers' => ['!test', '!trigger'],
        ];
    }
    
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->user()->admin === true;
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

### Testing Handlers
- There are two static helper methods you can call from your handlers class to aid you while developing:

### `getDTO()`
- Returns the handlers DTO class.
  - You must register your handler before using this method, otherwise it return `null`.

```php
use App\Bots\HelloBot;

dump(HelloBot::getDto());
```
```bash
RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO {#1668
  +class: "App\Bots\HelloBot"
  +alias: "hello"
  +description: "Say hello when someone says hi!"
  +name: "Hello Response"
  +unique: false
  +shouldAuthorize: false
  +triggers: array:2 [
    0 => "hello"
    1 => "hi"
    2 => "hey"
  ]
  +matchMethod: "contains:caseless"
}
```

### `testResolve(array $params = [])`
- Attempt to resolve the given parameters into a resolved bot handler. If it fails, the validation errors array will be returned.

```php
use App\Bots\HelloBot;

$failed = HelloBot::testResolve();
$resolved = HelloBot::testResolve([
  'cooldown' => 0,
  'admin_only' => false,
  'enabled' => true,
]);
```
```bash
## Resolved
RTippin\Messenger\DataTransferObjects\ResolvedBotHandlerDTO {#1677
  +handlerDTO: RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO {...}
  +matchMethod: "exact:caseless"
  +enabled: true
  +adminOnly: true
  +cooldown: 0
  +triggers: "hello|hi|hey"
  +payload: null
}

## Failed
array:4 [
  "cooldown" => array:1 [
    0 => "The cooldown field is required."
  ]
  "admin_only" => array:1 [
    0 => "The admin only field is required."
  ]
  "enabled" => array:1 [
    0 => "The enabled field is required."
  ]
]
```

---

## Register Handlers
- Once you are ready to make your handler available for use, head to your `MessengerServiceProvider` and add your handler classes using the facade `MessengerBots::registerHandlers()` method.
```php
<?php

namespace App\Providers;

use App\Bots\HelloBot;
use App\Bots\ReplyBot;
use App\Bots\TestBot;
use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Facades\MessengerBots;

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
            HelloBot::class,
            ReplyBot::class,
            TestBot::class,
        ]);
    }
}
```

---

# Creating Packaged Bots

- Packaged bots allow you to bundle a bot with many handlers the end user can install in one easy click. 
- An installation will store the `Bot` model along with each `BotAction` model that gets attached to the bot for each of the `BotActionHandler`'s you define and their parameters.
- During an install, any `BotActionHandler`'s that do not pass validation will be ignored.

**Create your packaged bot class and extend the [PackagedBot][link-packaged-bot] abstract class.**

**You can use the included command to generate the packaged bot class:**

```bash
php artisan messenger:make:packaged-bot TestBotPackage
```

- Your packages class must define the `public static getSettings()` and a `public static installs()` methods.

**Example**

```php
<?php

namespace App\Bots;

use App\Bots\HelloBot;
use App\Bots\ReplyBot;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Support\PackagedBot;

class TestBotPackage extends PackagedBot
{
    /**
     * The packages settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'test_package',
            'description' => 'Test package description.',
            'name' => 'Test Package',
        ];
    }

    /**
     * The handlers and their settings to install.
     *
     * @return array
     */
    public static function installs(): array
    {
        return [
            HelloBot::class => [
                'cooldown' => 300,
            ],
            ReplyBot::class => [
                'cooldown' => 120,
                'match' => MessengerBots::MATCH_CONTAINS_CASELESS,
                'triggers' => ['help', 'support'],
                'replies' => ['Why are you asking me?', 'I say google it!'],
            ],
        ];
    }
}
```

---

## `getSettings()`

**Must return an array defining the package's `alias`, `description`, and `name`.**

**`avatar`, `cooldown`, `enabled`, and `hide_actions` are optional overrides.**

- `alias [string]` Used to locate and install your packaged bot.
- `description [string]` The description of your packaged bot.
- `name [string]` The name of your packaged bot. The name will be used as the original `Bot` model's `name` when stored.
- `avatar (optional) [string]` Define the path to a local image file that will be used to display the package's avatar to the frontend, as well as stored for the `Bot` models initial `avatar`.
- `cooldown (optional) [int]` Cooldown the `Bot` model stored has after each action is triggered. Default of `0`.
- `enabled (optional) [bool]` Whether the `Bot` model is enabled or disabled. Default of `true`.
- `hide_actions (optional) [bool]` Whether the `Bot` model's actions are hidden or visible to others. Default of `false`.

**Example:**

```php
public static function getSettings(): array
{
    return [
        'alias' => 'test_package',
        'description' => 'Test package description.',
        'name' => 'Test Package',
        'avatar' => public_path('bots/test_bot.png'),
        'cooldown' => 30,
        'enabled' => false,
        'hide_actions' => true,
    ];
}
```

---

## `installs()`

**Must return an array defining each `BotActionHandler` the package installs.**

- Return the listing of `BotActionHandler` classes you want to be bundled with this install. 
- The array keys must be the `BotActionHandler` class. 
- For handlers that require you to set properties, or that you want to override certain defaults, you must define them as the value of the handler class key represented as an associative array. 
- If you want the handler to be installed multiple times with different parameters, you can define an array of arrays as the value. 
- The key/values of your parameters must match the default for a BotAction model, as well as include any parameters that are defined on the handlers rules for serializing a payload.
- `BotAction | BotActionHandler` keys include:
  - `enabled [bool]` Default of `true`.
  - `cooldown [int]` Default of `30`.
  - `admin_only [bool]` Default of `false`.
  - `triggers (optional) [array]` No default. You must define them if the `BotActionHandler` does not override them.
  - `match (optional) [string]` No default. You must define them if the `BotActionHandler` does not override them.
- Any parameters that are already defined in the bot handler class cannot be overridden. While installing, each `BotActionHandler` will pass through a validation process, and will be discarded if it fails validating.

---

**`BotActionHandler` That doesn't need any parameters defined:**

```php
public static function installs(): array
{
    return [
        HelloBot::class,
    ];
}
```

---

**`BotActionHandler` We want to use our own defaults:**

```php
public static function installs(): array
{
    return [
        HelloBot::class => [
            'cooldown' => 120,
            'enabled' => false,
            'admin_only' => true,
        ],
    ];
}
```

---

**`BotActionHandler` Requiring match, triggers, and custom rules to be defined:**

- Custom rules defined on the `BotActionHandler` must have the appropriate `key => value` specified as it passes through a validator and is used as the `BotAction` model's `payload`.

```php
public static function installs(): array
{
    return [
        ReplyBot::class => [
            'match' => MessengerBots::MATCH_CONTAINS_CASELESS,
            'triggers' => ['help', 'support'],
            'replies' => ['Why are you asking me?', 'I say google it!'],
        ],
    ];
}
```

---

**`BotActionHandler` We want to be installed multiple times with different parameters:**

- Please note that `BotActionHandler`'s flagged as unique will only be installed once.

```php
public static function installs(): array
{
    return [
        ReplyBot::class => [
            [
                'cooldown' => 300,
                'match' => MessengerBots::MATCH_CONTAINS_CASELESS,
                'triggers' => ['help', 'support'],
                'replies' => ['Why are you asking me?', 'I say google it!'],
            ],
            [
                'cooldown' => 120,
                'match' => MessengerBots::MATCH_EXACT,
                'triggers' => ['42'],
                'replies' => ['That is the answer to life.'],
            ],
        ],
    ];
}
```

---

**Installing multiple `BotActionHandler`'s with varying parameter requirements**

```php
public static function installs(): array
{
    return [
        HelloBot::class,
        ReplyBot::class => [
            [
                'cooldown' => 300,
                'match' => MessengerBots::MATCH_CONTAINS_CASELESS,
                'triggers' => ['help', 'support'],
                'replies' => ['Why are you asking me?', 'I say google it!'],
            ],
            [
                'cooldown' => 120,
                'match' => MessengerBots::MATCH_EXACT,
                'triggers' => ['42'],
                'replies' => ['That is the answer to life.'],
            ],
        ],
        TestBot::class => [
            'cooldown' => 120,
            'enabled' => false,
            'admin_only' => true,
        ],
    ];
}
```

---

### Bot Package Authorization
- To authorize the end user to install your package in a thread, you must define the `public authorize()` method and return boolean.
- This method will be called during the http request cycle, giving you access to the current auth/session/etc.
- If the end user is unauthorized, the package will be hidden from appearing in the available packages list while viewing packages to install.
- `BotActionHandler`'s listed to install that are flagged to authorize, and fail authorization for the end user, will be ignored.
- If you want to disable authorization for a single request cycle, you can use the facade call: `MessengerBots::shouldAuthorize(false)`

**Example**

```php
<?php

namespace App\Bots;

use App\Bots\HelloBot;
use RTippin\Messenger\Support\PackagedBot;

class TestBotPackage extends PackagedBot
{
    /**
     * The packages settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'test_package',
            'description' => 'Test package description.',
            'name' => 'Test Package',
        ];
    }

    /**
     * The handlers and their settings to install.
     *
     * @return array
     */
    public static function installs(): array
    {
        return [
            HelloBot::class,
        ];
    }
    
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->user()->admin === true;
    }
}
```

---

### Testing Packaged Bots
- There are two static helper methods you can call from your packaged bot class to aid you while developing:

### `getDTO()`
- Returns the packaged bot DTO class.
  - You must register your packaged bot before using this method, otherwise it returns `null`.

```php
use App\Bots\TestBotPackage;

dump(TestBotPackage::getDto());
```
```bash
RTippin\Messenger\DataTransferObjects\PackagedBotDTO {#1655
  +class: "App\Bots\TestBotPackage"
  +alias: "test_package"
  +name: "Test Package"
  +description: "Test package description."
  +avatar: null
  +avatarExtension: "png"
  +cooldown: 0
  +isEnabled: true
  +shouldHideActions: false
  +shouldInstallAvatar: false
  +shouldAuthorize: true
  +installs: Illuminate\Support\Collection {#1650
    #items: array:1 [
      0 => RTippin\Messenger\DataTransferObjects\PackagedBotInstallDTO {#1657
        +handler: RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO {...}
        +data: Illuminate\Support\Collection {#1659
          #items: array:1 [
            0 => array:3 [
              "enabled" => true
              "cooldown" => 30
              "admin_only" => false
            ]
          ]
        }
      }
    ]
  }
  +canInstall: Illuminate\Support\Collection {#1647
    #items: []
  }
  +alreadyInstalled: Illuminate\Support\Collection {#1649
    #items: []
  }
}
```

### `testInstalls()`
- Compile the installation listing without using filters or authorization. Returns resolved handlers as well as failing handlers and their validation errors.

```php
use App\Bots\TestBotPackage;

dump(TestBotPackage::testInstalls());
```
```bash
array:2 [
  "resolved" => Illuminate\Support\Collection {#1651
    #items: array:1 [
      0 => RTippin\Messenger\DataTransferObjects\ResolvedBotHandlerDTO {#1662
        +handlerDTO: RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO {...}
        +matchMethod: "exact:caseless"
        +enabled: true
        +adminOnly: true
        +cooldown: 0
        +triggers: "hello|hi|hey"
        +payload: null
      }
    ]
  }
  "failed" => Illuminate\Support\Collection {#1648
    #items: array:1 [
      0 => array:3 [
        "handler" => "App\Bots\ReplyBot"
        "data" => array:3 [
          "enabled" => true
          "cooldown" => 30
          "admin_only" => false
        ]
        "errors" => array:3 [
          "match" => array:1 [
            0 => "The match field is required."
          ]
          "triggers" => array:1 [
            0 => "The triggers field is required."
          ]
          "replies" => array:1 [
            0 => "The replies field is required."
          ]
        ]
      ]
    ]
  }
]
```

---

## Register Packaged Bots

- Once you are ready to make your packaged bots available for use, head to your `MessengerServiceProvider` and add your packaged bot classes using the facade `MessengerBots::registerPackagedBots()` method.
- Please note that when each `PackagedBot` is registered, any `BotActionHandler`'s defined to be installed will be registered automatically. 

```php
<?php

namespace App\Providers;

use App\Bots\TestBotPackage;
use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Facades\MessengerBots;

class MessengerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        MessengerBots::registerPackagedBots([
            TestBotPackage::class,
        ]);
    }
}
```

---

## Chat Bots API Flow
- With bots now enabled, and your packaged bots and bot handler's registered, you may use the API to manage a group threads bots.
- In order to create a new Bot in your group thread, the group settings must have `chat_bots` enabled, and the user creating the bot must be a group admin, or a participant with permissions to `manage_bots`.

#### Example storing a new bot

```js
axios.post('/api/messenger/threads/{thread}/bots', {
  name: "Test Bot",
  enabled: true,
  hide_actions: false,
  cooldown: 0
});
```

- Once a bot is created, you can view available handler's you may attach to the bot.
- When attaching a handler, our base rules that are always required are:
  - `handler : [string, your handler alias]`
  - `cooldown : [between:0,900]`
  - `admin_only ; [bool]`
  - `enabled : [bool]`
- If your handler defined the overrides `triggers` or `match`, then those parameters you defined can be omitted (backend also ignores) when posting to attach.
- When not overridden, the rules are as follows:
  - `triggers : [array, min:1]`
  - `match : [string, one of our match methods shown above]`
- Any additional fields required will be those you defined on your handler's `rules()`, if any.

#### Example adding our `HelloBot` we made above
- The match and triggers are already overridden, and we defined no extra rules on the handler. Only the base rules are required.
```js
axios.post('/api/messenger/threads/{thread}/bots/{bot}/actions', {
  "handler": "hello",
  "cooldown": 0,
  "admin_only": false,
  "enabled": true
});
```

#### Example adding our `ReplyBot` we made above
- We have no overrides on our ReplyBot, but we did define the custom rule for `replies`. All rules are required, as well as the extra rules we defined on the handler.
```js
axios.post('/api/messenger/threads/{thread}/bots/{bot}/actions', {
  "handler": "reply",
  "cooldown": 0,
  "admin_only": false,
  "enabled": true,
  "match": "contains:caseless",
  "triggers": [
    "help",
    "support"
  ],
  "replies": [
    "Why are you asking me?",
    "I say google it!"
  ]
});
```

- Now that the bot has been created and is enabled, as well as having both the custom handler's attached, we can trigger them!
- For `HelloBot`, sending a message that contains any of the triggers `hello|hi|hey` will cause the handler to send our message and reaction!
- For `ReplyBot`, sending a message that contains our triggers `help|support` will cause the handler to reply with the two messages `Why are you asking me?` and `I say google it!`,

#### Example installing packaged bot

```js
axios.post('/api/messenger/threads/{thread}/bots/packages', {
  alias: "test_package",
});
```

[link-messenger-bots]: https://github.com/RTippin/messenger-bots
[link-bot-subscriber]: https://github.com/RTippin/messenger/blob/1.x/src/Listeners/BotSubscriber.php
[link-action-handler]: https://github.com/RTippin/messenger/blob/1.x/src/Support/BotActionHandler.php
[link-packaged-bot]: https://github.com/RTippin/messenger/blob/1.x/src/Support/PackagedBot.php
[link-messenger-composer]: https://github.com/RTippin/messenger/blob/1.x/src/Support/MessengerComposer.php
[link-bots-service]: https://github.com/RTippin/messenger/blob/1.x/src/MessengerBots.php
[link-action-failed-event]: https://github.com/RTippin/messenger/blob/1.x/src/Events/BotActionFailedEvent.php
[link-action-handled-event]: https://github.com/RTippin/messenger/blob/1.x/src/Events/BotActionHandledEvent.php
[link-events-docs]: Events.md