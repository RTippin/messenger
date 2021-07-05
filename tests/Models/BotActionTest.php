<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class BotActionTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->assertDatabaseCount('bot_actions', 1);
        $this->assertDatabaseHas('bot_actions', [
            'id' => $action->id,
        ]);
        $this->assertInstanceOf(BotAction::class, $action);
        $this->assertSame(1, BotAction::count());
    }

    /** @test */
    public function it_has_relations()
    {
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->assertSame($action->bot_id, $action->bot->id);
        $this->assertInstanceOf(Bot::class, $action->bot);
        $this->assertSame($this->tippin->getKey(), $action->owner->getKey());
        $this->assertInstanceOf(MessengerProvider::class, $action->owner);
    }

    /** @test */
    public function it_cast_attributes()
    {
        BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        $action = BotAction::first();

        $this->assertInstanceOf(Carbon::class, $action->created_at);
        $this->assertInstanceOf(Carbon::class, $action->updated_at);
        $this->assertFalse($action->admin_only);
        $this->assertTrue($action->enabled);
        $this->assertSame(0, $action->cooldown);
    }

    /** @test */
    public function it_can_set_cooldown()
    {
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create(['cooldown' => 5]);

        $action->startCooldown();

        $this->assertTrue($action->isOnCooldown());
        $this->assertFalse($action->notOnCooldown());
    }

    /** @test */
    public function it_can_release_cooldown()
    {
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create(['cooldown' => 5]);

        $action->startCooldown();
        $this->assertTrue($action->isOnCooldown());
        $this->assertFalse($action->notOnCooldown());

        $action->releaseCooldown();
        $this->assertFalse($action->isOnCooldown());
        $this->assertTrue($action->notOnCooldown());
    }

    /** @test */
    public function it_has_any_cooldown_when_bot_on_cooldown()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create(['cooldown' => 5]);
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->create(['cooldown' => 5]);
        $bot->startCooldown();

        $this->assertFalse($action->isOnCooldown());
        $this->assertTrue($action->isOnAnyCooldown());
    }

    /** @test */
    public function it_has_any_cooldown_when_action_on_cooldown()
    {
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create(['cooldown' => 5]);
        $action->startCooldown();

        $this->assertFalse($action->bot->isOnCooldown());
        $this->assertTrue($action->isOnAnyCooldown());
    }

    /** @test */
    public function it_has_any_cooldown_when_action_and_bot_on_cooldown()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create(['cooldown' => 5]);
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->create(['cooldown' => 5]);
        $bot->startCooldown();
        $action->startCooldown();

        $this->assertTrue($action->bot->isOnCooldown());
        $this->assertTrue($action->isOnCooldown());
        $this->assertTrue($action->isOnAnyCooldown());
    }
}
