<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\OtherModel;

class MessengerTest extends FeatureTestCase
{
    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();
    }

    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.info'))
            ->assertUnauthorized();
    }

    /** @test */
    public function invalid_provider_is_forbidden()
    {
        $this->actingAs(new OtherModel);

        $this->getJson(route('api.messenger.info'))
            ->assertForbidden();
    }

    /** @test */
    public function messenger_info_was_successful()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.info'))
            ->assertSuccessful()
            ->assertJson([
                'siteName' => 'Messenger-Testbench',
                'messageImageUpload' => true,
                'calling' => true,
                'threadsIndexCount' => 100,
            ]);
    }

    /** @test */
    public function messenger_info_changes_when_set_dynamically()
    {
        $this->actingAs($this->tippin);

        Messenger::setCalling(false);
        Messenger::setMessageImageUpload(false);
        Messenger::setThreadsIndexCount(50);

        $this->getJson(route('api.messenger.info'))
            ->assertSuccessful()
            ->assertJson([
                'siteName' => 'Messenger-Testbench',
                'messageImageUpload' => false,
                'calling' => false,
                'threadsIndexCount' => 50,
            ]);
    }

    /** @test */
    public function new_user_has_no_unread_threads()
    {
        $this->actingAs($this->createJaneSmith());

        $this->getJson(route('api.messenger.unread.threads.count'))
            ->assertSuccessful()
            ->assertJson([
                'unread_threads_count' => 0,
            ]);
    }

    /** @test */
    public function user_has_unread_threads_count()
    {
        $this->createGroupThread($this->tippin);

        $this->createPrivateThread($this->tippin, $this->doe);

        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.unread.threads.count'))
            ->assertSuccessful()
            ->assertJson([
                'unread_threads_count' => 2,
            ]);
    }

    /** @test */
    public function new_user_has_no_active_calls()
    {
        $this->actingAs($this->createJaneSmith());

        $this->getJson(route('api.messenger.active.calls'))
            ->assertSuccessful()
            ->assertJsonCount(0);
    }

    /** @test */
    public function user_has_one_active_call()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $call = $this->createCall($thread, $this->tippin);

        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.active.calls'))
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'id' => $call->id,
                    'thread_id' => $thread->id,
                ],
            ]);
    }
}
