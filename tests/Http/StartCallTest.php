<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Broadcasting\CallJoinedBroadcast;
use RTippin\Messenger\Broadcasting\CallStartedBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\CallJoinedEvent;
use RTippin\Messenger\Events\CallStartedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StartCallTest extends FeatureTestCase
{
    private Thread $private;

    private Thread $group;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->group = $this->createGroupThread($this->tippin, $this->doe);
    }

    /** @test */
    public function non_participant_forbidden_to_start_call_in_private()
    {
        $this->actingAs($this->companyDevelopers());

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_start_call_in_group()
    {
        $this->actingAs($this->createSomeCompany());

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_without_permission_forbidden_to_start_call()
    {
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_on_pending_private()
    {
        $this->private->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'pending' => true,
            ]);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_when_active_call_exist()
    {
        $this->createCall($this->private, $this->tippin);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_when_creating_call_timeout_exist()
    {
        Cache::put("call:{$this->private->id}:starting", true);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_when_disabled_from_group_settings()
    {
        $this->group->update([
            'calling' => false,
        ]);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_when_disabled_from_config()
    {
        Messenger::setCalling(false);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_when_temporarily_disabled()
    {
        Messenger::disableCallsTemporarily(1);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_can_start_call_in_private()
    {
        $this->expectsEvents([
            CallStartedBroadcast::class,
            CallStartedEvent::class,
        ]);

        $this->doesntExpectEvents([
            CallJoinedBroadcast::class,
            CallJoinedEvent::class,
        ]);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->private->id,
                'options' => [
                    'setup_complete' => false,
                ],
            ]);
    }

    /** @test */
    public function admin_can_start_call_in_group()
    {
        $this->expectsEvents([
            CallStartedBroadcast::class,
            CallStartedEvent::class,
        ]);

        $this->doesntExpectEvents([
            CallJoinedBroadcast::class,
            CallJoinedEvent::class,
        ]);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function participant_with_permission_can_start_call_in_group()
    {
        $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'start_calls' => true,
            ]);

        $this->expectsEvents([
            CallStartedBroadcast::class,
            CallStartedEvent::class,
        ]);

        $this->doesntExpectEvents([
            CallJoinedBroadcast::class,
            CallJoinedEvent::class,
        ]);

        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();
    }
}
