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
    }
}
