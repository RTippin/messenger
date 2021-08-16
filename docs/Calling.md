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

- Video calling is disabled by default. If enabled, you must set the driver within our published `MessengerServiceProvider` (or any service providers boot method).
- You must create your own video driver implementing our contract [VideoDriver][link-video-driver]
- We provide an event subscriber ([CallSubscriber][link-call-subscriber]) to listen and react to calling events. You may choose to enable it, whether it puts jobs on the queue or not, and which queue channel its jobs are dispatched on.

---

## Call System

### Automation

**Based on the configs set above, you will want your queue worker listening for the `messenger` queue to handle call related jobs.**
```bash
php artisan queue:work --queue=messenger
```
**To automate ending active calls with no active participants, you may schedule our command once per minute within your `Kernel`**
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

- A call will be created for a given thread, adding the call model into the database, and firing our `CallStartedEvent`.
  - Once a call has been created, it usually still requires setup with a 3rd party video service.
- Our [CallSubscriber][link-call-subscriber] will listen for the `CallStartedEvent`, and dispatch the `SetupCall` job.
- `SetupCall` will handle the [VideoDriver][link-video-driver] `create` method, then updating the call models room ID/PIN/SECRET/PAYLOAD in the database.
  - This also marks the calls `setup_complete` as true. Your frontend can now have full access to the updated room details.
- While in a call, each participant must hit our call heartbeat endpoint, which puts them as active in cache for one minute. It is best to hit this endpoint every 30 seconds. This also ensures our scheduled command ends active calls with no active participants.
  - `GET api/messenger/threads/{thread}/calls/{call}/heartbeat`
- When a call is ended, the `call_ended` column will be filled with the current timestamp, and the call will no longer be considered active.
- Our [CallSubscriber][link-call-subscriber] will listen for the `CallEndedEvent`, and dispatch the `TeardownCall` job.
- `TeardownCall` will handle the [VideoDriver][link-video-driver] `destroy` method, then updating the call's `teardown_complete` to true.

---

## Setting up your VideoDriver implementation

**Check out our interface your class will need to implement**

### VideoDriver Interface
```php
<?php

namespace RTippin\Messenger\Contracts;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;

interface VideoDriver
{
    /**
     * Setup the video room for the call/thread. Set the values for
     * the getters listed below and return true/false if setup was
     * successful. We want access to both the thread and call to
     * decide what parameters we may want to use for setting up
     * a video room.
     *
     * @param Thread $thread
     * @param Call $call
     * @return bool
     */
    public function create(Thread $thread, Call $call): bool;

    /**
     * Teardown the video room for the call/thread. Return true/false
     * to let us know if it was successful. We only need the call
     * model as we should have saved any information needed for
     * teardown there.
     *
     * @param Call $call
     * @return mixed
     */
    public function destroy(Call $call): bool;

    /**
     * Called after a successful create.
     *
     * @return string|null
     */
    public function getRoomId(): ?string;

    /**
     * Called after a successful create.
     *
     * @return string|null
     */
    public function getRoomPin(): ?string;

    /**
     * Called after a successful create.
     *
     * @return string|null
     */
    public function getRoomSecret(): ?string;

    /**
     * Called after a successful create.
     *
     * @return mixed
     */
    public function getExtraPayload();
}
```

### Implementing the interface

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
            ? $thread->participants()->count() + 6
            : 4;
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

**Once your video driver is implemented, you must register it with `Messenger`**

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

[link-video-driver]: ../src/Contracts/VideoDriver.php
[link-call-subscriber]: ../src/Listeners/CallSubscriber.php
[link-janus-client]: https://github.com/RTippin/janus-client