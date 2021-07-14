<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\Fixtures\UserModel;
use RTippin\Messenger\Tests\HttpTestCase;

class ParticipantsTest extends HttpTestCase
{
    /** @test */
    public function non_participant_forbidden_to_view_participants()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.participants.index', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_can_view_group_participants()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.participants.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function user_can_view_private_participants()
    {
        $this->logCurrentRequest();
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.participants.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_view_paginated_participants()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $participant = Participant::factory()->for($thread)->owner(UserModel::factory()->create())->create();
        Participant::factory()->for($thread)->owner(UserModel::factory()->create())->count(2)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.participants.page', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_view_participant()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.participants.show', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $participant->id,
            ]);
    }
}
