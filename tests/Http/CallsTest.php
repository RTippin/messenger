<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallsTest extends FeatureTestCase
{
    private Thread $private;

    private Call $call;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $this->private = $this->createPrivateThread(
            $tippin,
            $this->userDoe()
        );

        $this->call = $this->createCall(
            $this->private,
            $tippin
        );
    }

    /** @test */
    public function non_participant_forbidden_to_view_calls()
    {
        $this->actingAs($this->companyDevelopers());

        $this->getJson(route('api.messenger.threads.calls.index', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_view_call()
    {
        $this->actingAs($this->companyDevelopers());

        $this->getJson(route('api.messenger.threads.calls.show', [
            'thread' => $this->private->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_can_view_calls()
    {
        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.calls.index', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function user_can_view_active_call()
    {
        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.threads.calls.show', [
            'thread' => $this->private->id,
            'call' => $this->call->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->call->id,
                'active' => true,
                'options' => [
                    'admin' => true,
                    'kicked' => false,
                    'room_id' => 123456789,
                    'room_pin' => 'PIN',
                    'setup_complete' => true,
                    'in_call' => true,
                    'left_call' => false,
                    'joined' => true,
                    'payload' => null,
                ],
            ]);
    }

    /** @test */
    public function viewing_non_active_call_missing_options_fields()
    {
        $this->call->update([
            'call_ended' => now(),
        ]);

        $this->actingAs($this->userTippin());

        $request = $this->getJson(route('api.messenger.threads.calls.show', [
            'thread' => $this->private->id,
            'call' => $this->call->id,
        ]));

        $request->assertSuccessful()
            ->assertJson([
                'id' => $this->call->id,
                'active' => false,
            ]);

        $this->assertArrayNotHasKey('options', $request->json());
    }

    /** @test */
    public function user_can_view_call_participants()
    {
        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.calls.participants.index', [
            'thread' => $this->private->id,
            'call' => $this->call->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1);
    }

    /** @test */
    public function user_can_view_call_participant()
    {
        $participant = $this->call->participants()->first();

        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.calls.participants.show', [
            'thread' => $this->private->id,
            'call' => $this->call->id,
            'participant' => $participant->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $participant->id,
            ]);
    }

    /** @test */
    public function kicked_participant_cannot_see_room_details()
    {
        $this->call->participants()
            ->first()
            ->update([
                'kicked' => true,
                'left_call' => now(),
            ]);

        $this->actingAs($this->userTippin());

        $request = $this->getJson(route('api.messenger.threads.calls.show', [
            'thread' => $this->private->id,
            'call' => $this->call->id,
        ]));

        $request->assertSuccessful()
            ->assertJson([
                'id' => $this->call->id,
                'options' => [
                    'admin' => true,
                    'kicked' => true,
                    'setup_complete' => true,
                    'in_call' => false,
                    'left_call' => true,
                    'joined' => true,
                ],
            ]);

        $this->assertArrayNotHasKey('room_id', $request->json()['options']);

        $this->assertArrayNotHasKey('room_pin', $request->json()['options']);

        $this->assertArrayNotHasKey('payload', $request->json()['options']);
    }
}