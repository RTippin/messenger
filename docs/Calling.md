# Calling

---

***Default Config:***

```php
'calling' => [
    'enabled' => env('MESSENGER_CALLING_ENABLED', false),
    'subscriber' => [
        'enabled' => true,
        'queued' => true,
        'channel' => 'messenger',
    ],
],
```

- Video calling is disabled by default. If enabled, you must set the driver implementation within the published `MessengerServiceProvider` (or any service providers boot method).
  - The [NullVideoBroker][link-video-broker] will be set as the default [VideoDriver][link-video-driver].
- You must create your own [VideoDriver][link-video-driver] implementation if you wish to interact with your 3rd party video provider of choice.
- Provided is an event subscriber ([CallSubscriber][link-call-subscriber]) to listen and react to calling events. You may choose to enable it, whether it puts jobs on the queue or not, and which queue channel its jobs are dispatched on.
- **Note:** The calling system uses atomic cache locks. Your cache driver must be one that supports locking to use the calling system.
  - See more on atomic locks: [https://laravel.com/docs/9.x/cache#atomic-locks](https://laravel.com/docs/9.x/cache#atomic-locks)

---

## Call System

### Automation

**Based on the configs set above, you will want your queue worker listening on the `messenger` queue to handle call related jobs.**
```bash
php artisan queue:work --queue=messenger
```
**To automate ending active calls with no active participants, you should schedule the call activity checker command once per minute within your applications `App\Console\Kernel`**
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
        $schedule->command('messenger:calls:check-activity')->everyMinute();
    }
}
```

### Flow

- You will bind your own video implementation to the [VideoDriver][link-video-driver] interface (more on that below).
- A call will be created for a given thread, adding the call model into the database, and firing the `CallStartedEvent`.
  - Once a call has been created, it usually still requires setup with your 3rd party video provider.
- The [CallSubscriber][link-call-subscriber] will listen for the `CallStartedEvent`, and dispatch the `SetupCall` job.
- `SetupCall` will handle the [VideoDriver][link-video-driver] `create` method, then updating the call models room ID/PIN/SECRET/PAYLOAD in the database.
  - This also marks the calls `setup_complete` as true. Your frontend can now have full access to the updated room details.
- While in a call, each participant must hit our call heartbeat endpoint, which puts them as active in cache for one minute. It is best to hit this endpoint every 30 seconds. This also ensures the scheduled call activity checker command ends active calls with no active participants.
  - `GET api/messenger/threads/{thread}/calls/{call}/heartbeat`
- When a call is ended, the `call_ended` column will be filled with the current timestamp, and the call will no longer be considered active.
- The [CallSubscriber][link-call-subscriber] will listen for the `CallEndedEvent`, and dispatch the `TeardownCall` job.
- `TeardownCall` will handle the [VideoDriver][link-video-driver] `destroy` method, then updating the call's `teardown_complete` to true.

---

## Implementing our [VideoDriver][link-video-driver]

**In this example, we chose Janus Media Server as our video provider. Please visit our [Janus Client][link-janus-client] package for more details.**

```php
<?php

namespace App\Brokers;

use RTippin\Janus\Exceptions\JanusApiException;
use RTippin\Janus\Exceptions\JanusPluginException;
use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Janus\Plugins\VideoRoom;

class JanusBroker implements VideoDriver
{
    /**
     * @var VideoRoom
     */
    protected VideoRoom $videoRoom;

    /**
     * @var string|null
     */
    protected ?string $roomId = null;

    /**
     * @var string|null
     */
    protected ?string $roomPin = null;

    /**
     * @var string|null
     */
    protected ?string $roomSecret = null;

    /**
     * @var string|null
     */
    protected ?string $extraPayload = null;

    /**
     * JanusBroker constructor.
     *
     * @param VideoRoom $videoRoom
     */
    public function __construct(VideoRoom $videoRoom)
    {
        $this->videoRoom = $videoRoom;
    }

    /**
     * @inheritDoc
     */
    public function create(Thread $thread, Call $call): bool
    {
        try {
            $janus = $this->videoRoom->create(
                $this->settings($thread)
            );
        } catch (JanusApiException | JanusPluginException $e) {
            report($e);

            return false;
        }

        $this->roomId = $janus['room'];
        $this->roomPin = $janus['pin'];
        $this->roomSecret = $janus['secret'];

        return true;
    }

    /**
     * @inheritDoc
     */
    public function destroy(Call $call): bool
    {
        try {
            $this->videoRoom->destroy(
                $call->room_id,
                $call->room_secret
            );
        } catch (JanusApiException | JanusPluginException $e) {
            report($e);

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getRoomId(): ?string
    {
        return $this->roomId;
    }

    /**
     * @inheritDoc
     */
    public function getRoomPin(): ?string
    {
        return $this->roomPin;
    }

    /**
     * @inheritDoc
     */
    public function getRoomSecret(): ?string
    {
        return $this->roomSecret;
    }

    /**
     * @inheritDoc
     */
    public function getExtraPayload(): ?string
    {
        return $this->extraPayload;
    }

    /**
     * @param Thread $thread
     * @return array
     */
    protected function settings(Thread $thread): array
    {
        return [
            'description' => $thread->id,
            'publishers' => $this->publishersCount($thread),
            'bitrate' => $this->bitrate($thread),
        ];
    }

    /**
     * @param Thread $thread
     * @return int
     */
    protected function publishersCount(Thread $thread): int
    {
        return $thread->isGroup()
            ? $thread->participants()->count() + 4
            : 2;
    }

    /**
     * @param Thread $thread
     * @return int
     */
    protected function bitrate(Thread $thread): int
    {
        return $thread->isGroup()
            ? 600000
            : 1024000;
    }
}
```

**Once your video driver is implemented, you must register it in your `MessengerServiceProvider`**

```php
<?php

namespace App\Providers;

use App\Brokers\JanusBroker;
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
        Messenger::setVideoDriver(JanusBroker::class);
    }
}
```

[link-video-driver]: https://github.com/RTippin/messenger/blob/1.x/src/Contracts/VideoDriver.php
[link-video-broker]: https://github.com/RTippin/messenger/blob/1.x/src/Brokers/NullVideoBroker.php
[link-call-subscriber]: https://github.com/RTippin/messenger/blob/1.x/src/Listeners/CallSubscriber.php
[link-janus-client]: https://github.com/RTippin/janus-client