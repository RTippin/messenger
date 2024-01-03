<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\FunBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;

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
    public function owner_returns_ghost_if_not_found()
    {
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->create([
            'owner_id' => 404,
            'owner_type' => $this->tippin->getMorphClass(),
        ]);

        $this->assertInstanceOf(GhostUser::class, $action->owner);
    }

    /** @test */
    public function it_is_owned_by_current_provider()
    {
        Messenger::setProvider($this->tippin);
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->assertTrue($action->isOwnedByCurrentProvider());
    }

    /** @test */
    public function it_is_not_owned_by_current_provider()
    {
        Messenger::setProvider($this->doe);
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->assertFalse($action->isOwnedByCurrentProvider());
    }

    /** @test */
    public function it_has_private_owner_channel()
    {
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->assertSame('user.'.$this->tippin->getKey(), $action->getOwnerPrivateChannel());
    }

    /** @test */
    public function it_cast_attributes()
    {
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->assertInstanceOf(Carbon::class, $action->created_at);
        $this->assertInstanceOf(Carbon::class, $action->updated_at);
        $this->assertFalse($action->admin_only);
        $this->assertTrue($action->enabled);
        $this->assertIsInt($action->cooldown);
    }

    /** @test */
    public function it_has_cooldown_cache_key()
    {
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->assertSame("bot:$action->bot_id:$action->id:cooldown", $action->getCooldownCacheKey());
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
        )->owner($this->tippin)->cooldown(5)->create();

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
        $bot = Bot::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->cooldown(5)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->cooldown(5)->create();
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
        )->owner($this->tippin)->cooldown(5)->create();
        $action->startCooldown();

        $this->assertFalse($action->bot->isOnCooldown());
        $this->assertTrue($action->isOnAnyCooldown());
    }

    /** @test */
    public function it_has_any_cooldown_when_action_and_bot_on_cooldown()
    {
        $bot = Bot::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->cooldown(5)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->cooldown(5)->create();
        $bot->startCooldown();
        $action->startCooldown();

        $this->assertTrue($action->bot->isOnCooldown());
        $this->assertTrue($action->isOnCooldown());
        $this->assertTrue($action->isOnAnyCooldown());
    }

    /** @test */
    public function it_has_triggers_and_match()
    {
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )
            ->owner($this->tippin)
            ->handler(SillyBotHandler::class)
            ->triggers('!testing|!is|!fun')
            ->match('starts:with:caseless')
            ->create();

        $this->assertSame(['!testing', '!is', '!fun'], $action->getTriggers());
        $this->assertSame('starts:with:caseless', $action->getMatchMethod());
    }

    /** @test */
    public function it_returns_empty_triggers_array_when_null()
    {
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )
            ->owner($this->tippin)
            ->handler(SillyBotHandler::class)
            ->triggers(null)
            ->create();

        $this->assertSame([], $action->getTriggers());
    }

    /** @test */
    public function it_uses_trigger_and_match_overrides_over_saved_values()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )
            ->owner($this->tippin)
            ->handler(FunBotHandler::class)
            ->triggers('!testing|!is|!fun')
            ->match('starts:with:caseless')
            ->create();

        $this->assertSame(['!test', '!more'], $action->getTriggers());
        $this->assertSame('exact:caseless', $action->getMatchMethod());
    }

    /** @test */
    public function it_has_actions_for_thread_cache_key()
    {
        $this->assertSame('thread:1234-5678:bot:actions', BotAction::getActionsForThreadCacheKey('1234-5678'));
    }

    /** @test */
    public function it_caches_valid_actions_for_thread()
    {
        $thread = Thread::factory()->group()->create();
        $actions = BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )
            ->owner($this->tippin)
            ->count(3)
            ->create();
        $cache = Cache::spy();
        $cache->shouldReceive('remember')->andReturn($actions);

        $getActions = BotAction::getActionsWithBotFromThread($thread->id);

        $this->assertInstanceOf(Collection::class, $getActions);
        $this->assertSame(3, $getActions->count());
        $cache->shouldHaveReceived('remember');
    }

    /** @test */
    public function it_clears_valid_actions_for_thread_cache()
    {
        $thread = Thread::factory()->group()->create();
        BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )
            ->owner($this->tippin)
            ->create();

        BotAction::getActionsWithBotFromThread($thread->id);
        $this->assertTrue(Cache::has(BotAction::getActionsForThreadCacheKey($thread->id)));

        BotAction::clearActionsCacheForThread($thread->id);
        $this->assertFalse(Cache::has(BotAction::getActionsForThreadCacheKey($thread->id)));
    }

    /**
     * @test
     *
     * @param  $triggers
     * @param  $result
     *
     * @dataProvider triggersGetFormatted
     */
    public function it_formats_triggers($triggers, $result)
    {
        $results = BotAction::formatTriggers($triggers);

        $this->assertSame($result, $results);
    }

    public static function triggersGetFormatted(): array
    {
        return [
            'Single trigger' => ['test', 'test'],
            'Single trigger array' => [['test'], 'test'],
            'Multiple triggers' => ['test|another', 'test|another'],
            'Multiple triggers array' => [['test', 'another'], 'test|another'],
            'Omits duplicates' => ['test|1|2|test|3|1', 'test|1|2|3'],
            'Omits duplicates in array' => [['test', '1', '2', 'test', '3', '1'], 'test|1|2|3'],
            'Can separate via commas' => ['test,1,2,3,4', 'test|1|2|3|4'],
            'Can separate via commas array' => [['test, 1,2, 3', '4'], 'test|1|2|3|4'],
            'Can separate via pipe' => ['test| 1|2| 3|4', 'test|1|2|3|4'],
            'Can separate via pipe in array' => [['test| 1|2| 3', '4'], 'test|1|2|3|4'],
            'Can separate via comma and pipe' => ['test, 1|2| 3|4,5', 'test|1|2|3|4|5'],
            'Can separate via comma and pipe in array' => [['test, 1|2| 3', '4,5'], 'test|1|2|3|4|5'],
            'Multiple filters combined' => ['test, 1|2| 3,4,5,1|2,|6', 'test|1|2|3|4|5|6'],
            'Multiple filters combined in array' => [['test, 1|2| 3', '4,5', '1|2', ',|', '6'], 'test|1|2|3|4|5|6'],
            'Removes empty values' => ['test,1,2,||3|3,||test|1|2|3', 'test|1|2|3'],
            'Removes empty values in array' => [['test', '1', '2', ',', '|', '|3|3,||'], 'test|1|2|3'],
        ];
    }
}
