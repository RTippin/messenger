<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class BotTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        $this->assertDatabaseCount('bots', 1);
        $this->assertDatabaseHas('bots', [
            'id' => $bot->id,
        ]);
        $this->assertInstanceOf(Bot::class, $bot);
        $this->assertSame(1, Bot::count());
    }

    /** @test */
    public function it_has_relations()
    {
        BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        $bot = Bot::first();

        $this->assertSame($bot->thread_id, $bot->thread->id);
        $this->assertSame($this->tippin->getKey(), $bot->owner->getKey());
        $this->assertInstanceOf(Thread::class, $bot->thread);
        $this->assertInstanceOf(MessengerProvider::class, $bot->owner);
        $this->assertInstanceOf(Collection::class, $bot->actions);
        $this->assertSame(BotAction::first()->id, $bot->actions->first()->id);
    }

    /** @test */
    public function owner_returns_ghost_if_not_found()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->create([
            'owner_id' => 404,
            'owner_type' => $this->tippin->getMorphClass(),
        ]);

        $this->assertInstanceOf(GhostUser::class, $bot->owner);
    }

    /** @test */
    public function it_cast_attributes()
    {
        Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();
        $bot = Bot::first();

        $this->assertInstanceOf(Carbon::class, $bot->created_at);
        $this->assertInstanceOf(Carbon::class, $bot->updated_at);
        $this->assertTrue($bot->enabled);
    }

    /** @test */
    public function it_has_avatar_route()
    {
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create(['avatar' => 'test.png']);
        $avatar = [
            'sm' => "/messenger/threads/$thread->id/bots/$bot->id/avatar/sm/test.png",
            'md' => "/messenger/threads/$thread->id/bots/$bot->id/avatar/md/test.png",
            'lg' => "/messenger/threads/$thread->id/bots/$bot->id/avatar/lg/test.png",
        ];
        $apiAvatarApi = [
            'sm' => "/api/messenger/threads/$thread->id/bots/$bot->id/avatar/sm/test.png",
            'md' => "/api/messenger/threads/$thread->id/bots/$bot->id/avatar/md/test.png",
            'lg' => "/api/messenger/threads/$thread->id/bots/$bot->id/avatar/lg/test.png",
        ];

        $this->assertSame($avatar['sm'], $bot->getProviderAvatarRoute('sm'));
        $this->assertSame($avatar['md'], $bot->getProviderAvatarRoute('md'));
        $this->assertSame($avatar['lg'], $bot->getProviderAvatarRoute('lg'));
        $this->assertSame($apiAvatarApi['sm'], $bot->getProviderAvatarRoute('sm', true));
        $this->assertSame($apiAvatarApi['md'], $bot->getProviderAvatarRoute('md', true));
        $this->assertSame($apiAvatarApi['lg'], $bot->getProviderAvatarRoute('lg', true));
    }
}
