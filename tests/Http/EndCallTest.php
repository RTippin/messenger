<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\CallEndedBroadcast;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class EndCallTest extends FeatureTestCase
{
    private Thread $group;

    private Call $call;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $this->group = $this->createGroupThread($tippin, $this->userDoe());

        $this->call = $this->createCall($this->group, $tippin);
    }

    /** @test */
    public function end_call_must_be_a_post()
    {
        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.threads.calls.end', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertStatus(405);
    }

    /** @test */
    public function end_call_on_missing_call_not_found()
    {
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $this->group->id,
            'call' => '123-456-789',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function non_call_participant_forbidden_to_end_call()
    {
        $this->actingAs($this->userDoe());

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function inactive_participant_forbidden_to_end_call()
    {
        $this->call->participants()
            ->first()
            ->update([
                'left_call' => now(),
            ]);

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function kicked_participant_forbidden_to_end_call()
    {
        $this->call->participants()
            ->first()
            ->update([
                'kicked' => true,
            ]);

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_call_admin_participant_forbidden_to_end_call()
    {
        $doe = $this->userDoe();

        $this->call->participants()->create([
            'owner_id' => $doe->getKey(),
            'owner_type' => get_class($doe),
        ]);

        $this->actingAs($doe);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_end_call()
    {
        Event::fake([
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);

        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertSuccessful();

        $this->assertNotNull($this->call->fresh()->call_ended);

        $this->assertNotNull($this->call->participants()->first()->left_call);

        Event::assertDispatched(function (CallEndedBroadcast $event) use ($tippin, $doe) {
            $this->assertContains('private-user.'.$tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->call->id, $event->broadcastWith()['id']);
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (CallEndedEvent $event) {
            return $this->call->id === $event->call->id;
        });
    }

    /** @test */
    public function call_creator_can_end_call()
    {
        $this->expectsEvents([
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);

        $doe = $this->userDoe();

        $call = $this->createCall($this->group, $doe);

        $this->actingAs($doe);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $this->group->id,
            'call' => $call->id,
        ]))
            ->assertSuccessful();

        $this->assertNotNull($call->fresh()->call_ended);
    }
}
