<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Bots\DestroyBotAvatar;
use RTippin\Messenger\Events\BotAvatarEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\BotAvatarMessage;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class DestroyBotAvatarTest extends FeatureTestCase
{
    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setBots(false);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Bot avatars are currently disabled.');

        app(DestroyBotAvatar::class)->execute($bot);
    }

    /** @test */
    public function it_throws_exception_if_bot_avatar_disabled()
    {
        Messenger::setBotAvatars(false);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Bot avatars are currently disabled.');

        app(DestroyBotAvatar::class)->execute($bot);
    }

    /** @test */
    public function it_updates_bot_avatar()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create(['avatar' => 'avatar.jpg']);

        app(DestroyBotAvatar::class)->execute($bot);

        $this->assertDatabaseHas('bots', [
            'id' => $bot->id,
            'avatar' => null,
        ]);
    }

    /** @test */
    public function it_removes_avatar_from_disk()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create(['avatar' => 'avatar.jpg']);
        UploadedFile::fake()->image('avatar.jpg')->storeAs($bot->getAvatarDirectory(), 'avatar.jpg', [
            'disk' => 'messenger',
        ]);

        app(DestroyBotAvatar::class)->execute($bot);

        Storage::disk('messenger')->assertMissing($bot->getAvatarDirectory().'/avatar.jpg');
    }

    /** @test */
    public function it_fires_events_and_clears_actions_cache()
    {
        BaseMessengerAction::enableEvents();
        Messenger::setProvider($this->tippin);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create(['avatar' => 'avatar.jpg']);
        $cache = Cache::spy();
        Event::fake([
            BotAvatarEvent::class,
        ]);

        app(DestroyBotAvatar::class)->execute($bot);

        $cache->shouldHaveReceived('forget');
        Event::assertDispatched(function (BotAvatarEvent $event) use ($bot) {
            return $bot->id === $event->bot->id;
        });
    }

    /** @test */
    public function it_doesnt_fires_events_or_clear_actions_cache_if_no_avatar_to_remove()
    {
        BaseMessengerAction::enableEvents();
        Messenger::setProvider($this->tippin);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create(['avatar' => null]);
        $cache = Cache::spy();
        Event::fake([
            BotAvatarEvent::class,
        ]);

        app(DestroyBotAvatar::class)->execute($bot);

        $cache->shouldNotHaveReceived('forget');
        Event::assertNotDispatched(BotAvatarEvent::class);
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setProvider($this->tippin);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create(['avatar' => 'avatar.jpg']);

        app(DestroyBotAvatar::class)->execute($bot);

        Bus::assertDispatched(BotAvatarMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setProvider($this->tippin)->setSystemMessageSubscriber('queued', false);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create(['avatar' => 'avatar.jpg']);

        app(DestroyBotAvatar::class)->execute($bot);

        Bus::assertDispatchedSync(BotAvatarMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setProvider($this->tippin)->setSystemMessageSubscriber('enabled', false);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create(['avatar' => 'avatar.jpg']);

        app(DestroyBotAvatar::class)->execute($bot);

        Bus::assertNotDispatched(BotAvatarMessage::class);
    }
}
