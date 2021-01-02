<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class AddParticipantsTest extends FeatureTestCase
{
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->group = $this->createGroupThread(
            $tippin,
            $doe
        );

        $this->createFriends(
            $tippin,
            $doe
        );

        $this->createFriends(
            $tippin,
            $this->companyDevelopers()
        );
    }

    /** @test */
    public function non_participant_forbidden_to_view_add_participants()
    {
        $this->actingAs($this->companyDevelopers());

        $this->getJson(route('api.messenger.threads.add.participants', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_view_add_participants_and_has_one_result()
    {
        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.threads.add.participants', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'party' => [
                        'name' => 'Developers',
                    ],
                    'party_id' => $this->companyDevelopers()->getKey(),
                ],
            ]);
    }
}