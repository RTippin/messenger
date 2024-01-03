<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class BotTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $bot = Bot::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create();

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
        $bot = Bot::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->create();

        $this->assertSame($bot->thread_id, $bot->thread->id);
        $this->assertSame($this->tippin->getKey(), $bot->owner->getKey());
        $this->assertInstanceOf(Thread::class, $bot->thread);
        $this->assertInstanceOf(MessengerProvider::class, $bot->owner);
        $this->assertInstanceOf(Collection::class, $bot->actions);
        $this->assertInstanceOf(Collection::class, $bot->validActions);
        $this->assertInstanceOf(Collection::class, $bot->validUniqueActions);
        $this->assertSame($action->id, $bot->actions->first()->id);
    }

    /** @test */
    public function owner_returns_ghost_if_not_found()
    {
        $bot = Bot::factory()->for(
            Thread::factory()->group()->create()
        )->create([
            'owner_id' => 404,
            'owner_type' => $this->tippin->getMorphClass(),
        ]);

        $this->assertInstanceOf(GhostUser::class, $bot->owner);
    }

    /** @test */
    public function it_is_owned_by_current_provider()
    {
        Messenger::setProvider($this->tippin);
        $bot = Bot::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create();

        $this->assertTrue($bot->isOwnedByCurrentProvider());
    }

    /** @test */
    public function it_is_not_owned_by_current_provider()
    {
        Messenger::setProvider($this->doe);
        $bot = Bot::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create();

        $this->assertFalse($bot->isOwnedByCurrentProvider());
    }

    /** @test */
    public function it_has_private_owner_channel()
    {
        $bot = Bot::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create();

        $this->assertSame('user.'.$this->tippin->getKey(), $bot->getOwnerPrivateChannel());
    }

    /** @test */
    public function it_cast_attributes()
    {
        $bot = Bot::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create();

        $this->assertInstanceOf(Carbon::class, $bot->created_at);
        $this->assertInstanceOf(Carbon::class, $bot->updated_at);
        $this->assertTrue($bot->enabled);
        $this->assertFalse($bot->hide_actions);
        $this->assertSame(0, $bot->cooldown);
    }

    /** @test */
    public function it_has_avatar_route()
    {
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create(['avatar' => 'test.png']);
        $avatar = [
            'sm' => "/messenger/assets/threads/$thread->id/bots/$bot->id/avatar/sm/test.png",
            'md' => "/messenger/assets/threads/$thread->id/bots/$bot->id/avatar/md/test.png",
            'lg' => "/messenger/assets/threads/$thread->id/bots/$bot->id/avatar/lg/test.png",
        ];

        $this->assertSame($avatar['sm'], $bot->getProviderAvatarRoute());
        $this->assertSame($avatar['md'], $bot->getProviderAvatarRoute('md'));
        $this->assertSame($avatar['lg'], $bot->getProviderAvatarRoute('lg'));
    }

    /** @test */
    public function it_has_cooldown_cache_key()
    {
        $bot = Bot::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create();

        $this->assertSame("bot:$bot->id:cooldown", $bot->getCooldownCacheKey());
    }

    /** @test */
    public function it_can_set_cooldown()
    {
        $bot = Bot::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->cooldown(5)->create();

        $bot->startCooldown();

        $this->assertTrue($bot->isOnCooldown());
        $this->assertFalse($bot->notOnCooldown());
    }

    /** @test */
    public function it_can_release_cooldown()
    {
        $bot = Bot::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->cooldown(5)->create();

        $bot->startCooldown();
        $this->assertTrue($bot->isOnCooldown());
        $this->assertFalse($bot->notOnCooldown());

        $bot->releaseCooldown();
        $this->assertFalse($bot->isOnCooldown());
        $this->assertTrue($bot->notOnCooldown());
    }
}
