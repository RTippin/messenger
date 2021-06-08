<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Bots\DestroyBotAvatar;
use RTippin\Messenger\Events\BotAvatarEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class DestroyBotAvatarTest extends FeatureTestCase
{
    /** @test */
    public function it_throws_exception_if_disabled()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Bot Avatar removal is currently disabled.');

        app(DestroyBotAvatar::class)->execute($bot);
    }

    /** @test */
    public function it_updates_bot_avatar()
    {
        Messenger::setBots(true);
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
        Messenger::setBots(true);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create(['avatar' => 'avatar.jpg']);
        UploadedFile::fake()->image('avatar.jpg')->storeAs($bot->getAvatarDirectory(), 'avatar.jpg', [
            'disk' => 'messenger',
        ]);

        app(DestroyBotAvatar::class)->execute($bot);

        Storage::disk('messenger')->assertMissing($bot->getAvatarDirectory().'/avatar.jpg');
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Messenger::setProvider($this->tippin)->setBots(true);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create(['avatar' => 'avatar.jpg']);
        Event::fake([
            BotAvatarEvent::class,
        ]);

        app(DestroyBotAvatar::class)->execute($bot);

        Event::assertDispatched(function (BotAvatarEvent $event) use ($bot) {
            return $bot->id === $event->bot->id;
        });
    }
}