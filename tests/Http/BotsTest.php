<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Tests\FeatureTestCase;

class BotsTest extends FeatureTestCase
{
    /** @test */
    public function it_list_thread_bots()
    {
        $thread = $this->createGroupThread($this->tippin);
        Bot::factory()->for($thread)->owner($this->tippin)->count(2)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2);
    }

    /** @test */
    public function it_stores_thread_bot()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.store', [
            'thread' => $thread->id,
        ]), [
            'name' => 'Test Bot',
        ])
            ->assertSuccessful()
            ->assertJson([
                'name' => 'Test Bot',
                'owner_id' => $this->tippin->getKey(),
                'owner_type' => $this->tippin->getMorphClass(),
            ]);
    }

    /** @test */
    public function it_shows_bot()
    {
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create(['name' => 'Test Bot']);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.show', [
            'thread' => $thread->id,
            'bot' => $bot->id
        ]))
            ->assertSuccessful()
            ->assertJson([
                'name' => 'Test Bot',
                'owner_id' => $this->tippin->getKey(),
                'owner_type' => $this->tippin->getMorphClass(),
            ]);
    }
}
