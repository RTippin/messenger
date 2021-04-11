<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\CallEndedBroadcast;
use RTippin\Messenger\Broadcasting\CallIgnoredBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Events\CallIgnoredEvent;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class IgnoreCallTest extends FeatureTestCase
{
    private Thread $group;
    private Call $call;
    private MessengerProvider $tippin;
    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->doe = $this->userDoe();
        $this->group = $this->createGroupThread($this->tippin, $this->doe);
        $this->call = $this->createCall($this->group, $this->tippin);
    }

    /** @test */
    public function ignoring_missing_call_not_found()
    {
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.calls.ignore', [
            'thread' => $this->group->id,
            'call' => '123-456-789',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function call_participant_forbidden_to_ignore_call()
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.ignore', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_ignore_inactive_call()
    {
        $this->call->update([
            'call_ended' => now(),
        ]);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.ignore', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_call_participant_can_ignore_call()
    {
        $this->actingAs($this->doe);

        $this->expectsEvents([
            CallIgnoredBroadcast::class,
            CallIgnoredEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.calls.ignore', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function ignoring_private_call_ends_call()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);
        $call = $this->createCall($private, $this->tippin);
        $this->actingAs($this->doe);

        $this->expectsEvents([
            CallIgnoredBroadcast::class,
            CallIgnoredEvent::class,
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.calls.ignore', [
            'thread' => $private->id,
            'call' => $call->id,
        ]))
            ->assertSuccessful();
    }
}
