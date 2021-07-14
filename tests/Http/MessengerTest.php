<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Tests\Fixtures\OtherModel;
use RTippin\Messenger\Tests\HttpTestCase;

class MessengerTest extends HttpTestCase
{
    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.info'))
            ->assertUnauthorized();
    }

    /** @test */
    public function invalid_provider_is_forbidden()
    {
        $this->logCurrentRequest();
        $this->actingAs(new OtherModel);

        $this->getJson(route('api.messenger.info'))
            ->assertForbidden();
    }

    /** @test */
    public function messenger_info_was_successful()
    {
        $this->logCurrentRequest();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.info'))
            ->assertSuccessful()
            ->assertJson([
                'messageImageUpload' => true,
                'calling' => true,
                'threadsIndexCount' => 100,
            ]);
    }

    /** @test */
    public function messenger_info_changes_when_set_dynamically()
    {
        Messenger::setCalling(false);
        Messenger::setMessageImageUpload(false);
        Messenger::setThreadsIndexCount(50);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.info'))
            ->assertSuccessful()
            ->assertJson([
                'messageImageUpload' => false,
                'calling' => false,
                'threadsIndexCount' => 50,
            ]);
    }

    /** @test */
    public function user_has_no_unread_threads()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.unread.threads.count'))
            ->assertSuccessful()
            ->assertJson([
                'unread_threads_count' => 0,
            ]);
    }

    /** @test */
    public function user_has_unread_thread()
    {
        $this->logCurrentRequest();
        $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.unread.threads.count'))
            ->assertSuccessful()
            ->assertJson([
                'unread_threads_count' => 1,
            ]);
    }

    /** @test */
    public function user_has_no_active_calls()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.active.calls'))
            ->assertSuccessful()
            ->assertJsonCount(0);
    }

    /** @test */
    public function user_has_active_call()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.active.calls'))
            ->assertSuccessful()
            ->assertJsonCount(1);
    }
}
