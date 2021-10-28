<?php

namespace RTippin\Messenger\Tests\Actions;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Bots\StoreBotAvatar;
use RTippin\Messenger\Events\BotAvatarEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\BotAvatarMessage;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\FileService;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreBotAvatarTest extends FeatureTestCase
{
    /** @test */
    public function it_throws_exception_if_bots_disabled()
    {
        Messenger::setBots(false);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Bot avatars are currently disabled.');

        app(StoreBotAvatar::class)->execute($bot, UploadedFile::fake()->image('avatar.jpg'));
    }

    /** @test */
    public function it_throws_exception_if_bot_avatar_disabled()
    {
        Messenger::setBotAvatars(false);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Bot avatars are currently disabled.');

        app(StoreBotAvatar::class)->execute($bot, UploadedFile::fake()->image('avatar.jpg'));
    }

    /** @test */
    public function it_throws_exception_if_transaction_fails_and_removes_uploaded_avatar()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create(['avatar' => 'avatar.jpg']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Storage Error');
        $fileService = $this->mock(FileService::class);
        $fileService->shouldReceive([
            'setType' => Mockery::self(),
            'setDisk' => Mockery::self(),
            'setDirectory' => Mockery::self(),
            'upload' => 'avatar.jpg',
        ]);
        $fileService->shouldReceive('destroy')->andThrow(new Exception('Storage Error'));

        app(StoreBotAvatar::class)->execute($bot, UploadedFile::fake()->image('avatar.jpg'));
    }

    /** @test */
    public function it_updates_bot_avatar()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();
        Carbon::setTestNow($updated = now()->addMinutes(5));

        app(StoreBotAvatar::class)->execute($bot, UploadedFile::fake()->image('avatar.jpg'));

        $this->assertDatabaseHas('bots', [
            'id' => $bot->id,
            'updated_at' => $updated,
        ]);
        $this->assertNotNull($bot->avatar);
    }

    /** @test */
    public function it_stores_bot_avatar_and_clears_actions_cache()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();
        $cache = Cache::spy();

        app(StoreBotAvatar::class)->execute($bot, UploadedFile::fake()->image('avatar.jpg'));

        $cache->shouldHaveReceived('forget');
        Storage::disk('messenger')->assertExists($bot->getAvatarPath());
    }

    /** @test */
    public function it_removes_existing_avatar_from_disk()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create(['avatar' => 'avatar.jpg']);
        UploadedFile::fake()->image('avatar.jpg')->storeAs($bot->getAvatarDirectory(), 'avatar.jpg', [
            'disk' => 'messenger',
        ]);

        app(StoreBotAvatar::class)->execute($bot, UploadedFile::fake()->image('avatar.jpg'));

        $this->assertNotSame('avatar.jpg', $bot->avatar);
        Storage::disk('messenger')->assertExists($bot->getAvatarPath());
        Storage::disk('messenger')->assertMissing($bot->getAvatarDirectory().'/avatar.jpg');
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Messenger::setProvider($this->tippin);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();
        Event::fake([
            BotAvatarEvent::class,
        ]);

        app(StoreBotAvatar::class)->execute($bot, UploadedFile::fake()->image('avatar.jpg'));

        Event::assertDispatched(function (BotAvatarEvent $event) use ($bot) {
            return $bot->id === $event->bot->id;
        });
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setProvider($this->tippin);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        app(StoreBotAvatar::class)->execute($bot, UploadedFile::fake()->image('avatar.jpg'));

        Bus::assertDispatched(BotAvatarMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setProvider($this->tippin)->setSystemMessageSubscriber('queued', false);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        app(StoreBotAvatar::class)->execute($bot, UploadedFile::fake()->image('avatar.jpg'));

        Bus::assertDispatchedSync(BotAvatarMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setProvider($this->tippin)->setSystemMessageSubscriber('enabled', false);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        app(StoreBotAvatar::class)->execute($bot, UploadedFile::fake()->image('avatar.jpg'));

        Bus::assertNotDispatched(BotAvatarMessage::class);
    }
}
