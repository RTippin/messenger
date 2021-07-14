<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Tests\HttpTestCase;

class SystemMessageTest extends HttpTestCase
{
    /** @test */
    public function user_can_view_logs()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        Message::factory()->for($thread)->owner($this->tippin)->system()->count(4)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.logs', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(4, 'data');
    }

    /** @test */
    public function user_can_view_paginated_logs()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        Message::factory()->for($thread)->owner($this->tippin)->system()->count(2)->create();
        $system = Message::factory()->for($thread)->owner($this->tippin)->system()->create();
        Message::factory()->for($thread)->owner($this->tippin)->system()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.logs.page', [
            'thread' => $thread->id,
            'log' => $system->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }
}
