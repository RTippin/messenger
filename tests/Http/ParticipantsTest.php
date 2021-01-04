<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ParticipantsTest extends FeatureTestCase
{
    private Thread $private;

    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->group = $this->createGroupThread(
            $tippin,
            $doe,
            $this->companyDevelopers()
        );

        $this->private = $this->createPrivateThread(
            $tippin,
            $doe
        );
    }

    /** @test */
    public function guest_is_unauthorized_to_view_participants()
    {
        $this->getJson(route('api.messenger.threads.participants.index', [
            'thread' => $this->group->id,
        ]))
            ->assertUnauthorized();
    }

    /** @test */
    public function non_participant_forbidden_to_view_participants()
    {
        $this->actingAs($this->createJaneSmith());

        $this->getJson(route('api.messenger.threads.participants.index', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_can_view_group_participants()
    {
        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.participants.index', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function user_can_view_private_participants()
    {
        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.participants.index', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_view_private_participant()
    {
        $doe = $this->userDoe();

        $participant = $this->private->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.threads.participants.show', [
            'thread' => $this->private->id,
            'participant' => $participant->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $participant->id,
                'owner' => [
                    'name' => 'John Doe',
                ],
            ]);
    }

    /** @test */
    public function user_can_view_group_participant()
    {
        $developers = $this->companyDevelopers();

        $participant = $this->group->participants()
            ->where('owner_id', '=', $developers->getKey())
            ->where('owner_type', '=', get_class($developers))
            ->first();

        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.participants.show', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $participant->id,
                'owner' => [
                    'name' => 'Developers',
                ],
            ]);
    }
}
