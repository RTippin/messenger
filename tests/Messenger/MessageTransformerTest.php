<?php

namespace RTippin\Messenger\Tests\Messenger;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\MessageTransformer;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessageTransformerTest extends FeatureTestCase
{
    /** @test */
    public function it_makes_joined_with_invite()
    {
        $thread = Thread::factory()->group()->create();

        $this->assertSame([
            $thread,
            $this->tippin,
            'joined',
            'PARTICIPANT_JOINED_WITH_INVITE'
        ], MessageTransformer::makeJoinedWithInvite($thread, $this->tippin));
    }

    /** @test */
    public function it_makes_video_call()
    {
        $thread = Thread::factory()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();

        $this->assertSame([
            $thread,
            $this->tippin,
            '{"call_id":"'.$call->id.'"}',
            'VIDEO_CALL'
        ], MessageTransformer::makeVideoCall($thread, $this->tippin, $call));
    }

    /** @test */
    public function it_makes_group_avatar_changed()
    {
        $thread = Thread::factory()->group()->create();

        $this->assertSame([
            $thread,
            $this->tippin,
            'updated the avatar',
            'GROUP_AVATAR_CHANGED'
        ], MessageTransformer::makeGroupAvatarChanged($thread, $this->tippin));
    }

    /** @test */
    public function it_makes_private_thread_archived()
    {
        $thread = Thread::factory()->create();

        $this->assertSame([
            $thread,
            $this->tippin,
            'archived the conversation',
            'THREAD_ARCHIVED'
        ], MessageTransformer::makeThreadArchived($thread, $this->tippin));
    }

    /** @test */
    public function it_makes_group_thread_archived()
    {
        $thread = Thread::factory()->group()->create();

        $this->assertSame([
            $thread,
            $this->tippin,
            'archived the group',
            'THREAD_ARCHIVED'
        ], MessageTransformer::makeThreadArchived($thread, $this->tippin));
    }

    /** @test */
    public function it_makes_group_created()
    {
        $thread = Thread::factory()->group()->create();

        $this->assertSame([
            $thread,
            $this->tippin,
            'created Group Name',
            'GROUP_CREATED'
        ], MessageTransformer::makeGroupCreated($thread, $this->tippin, 'Group Name'));
    }

    /** @test */
    public function it_makes_group_renamed()
    {
        $thread = Thread::factory()->group()->create();

        $this->assertSame([
            $thread,
            $this->tippin,
            'renamed the group to Renamed',
            'GROUP_RENAMED'
        ], MessageTransformer::makeGroupRenamed($thread, $this->tippin, 'Renamed'));
    }

    /** @test */
    public function it_makes_participant_demoted()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();

        $make = MessageTransformer::makeParticipantDemoted($thread, $this->tippin, $participant);

        $json = json_decode($make[2], true);

        $this->assertSame($thread, $make[0]);
        $this->assertSame($this->tippin, $make[1]);
        $this->assertSame('DEMOTED_ADMIN', $make[3]);
        $this->assertSame($json, [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
        ]);
    }

    /** @test */
    public function it_makes_participant_promoted()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();

        $make = MessageTransformer::makeParticipantPromoted($thread, $this->tippin, $participant);

        $json = json_decode($make[2], true);

        $this->assertSame($thread, $make[0]);
        $this->assertSame($this->tippin, $make[1]);
        $this->assertSame('PROMOTED_ADMIN', $make[3]);
        $this->assertSame($json, [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
        ]);
    }

    /** @test */
    public function it_makes_group_left()
    {
        $thread = Thread::factory()->group()->create();

        $this->assertSame([
            $thread,
            $this->tippin,
            'left',
            'PARTICIPANT_LEFT_GROUP'
        ], MessageTransformer::makeGroupLeft($thread, $this->tippin));
    }

    /** @test */
    public function it_makes_removed_from_group()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();

        $make = MessageTransformer::makeRemovedFromGroup($thread, $this->tippin, $participant);

        $json = json_decode($make[2], true);

        $this->assertSame($thread, $make[0]);
        $this->assertSame($this->tippin, $make[1]);
        $this->assertSame('PARTICIPANT_REMOVED', $make[3]);
        $this->assertSame($json, [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
        ]);
    }

    /** @test */
    public function it_makes_participants_added()
    {
        $thread = Thread::factory()->group()->create();
        $participant1 = Participant::factory()->for($thread)->owner($this->tippin)->create();
        $participant2 = Participant::factory()->for($thread)->owner($this->developers)->create();

        $make = MessageTransformer::makeParticipantsAdded($thread, $this->tippin, collect([$participant1, $participant2]));

        $json = json_decode($make[2], true);

        $this->assertSame($thread, $make[0]);
        $this->assertSame($this->tippin, $make[1]);
        $this->assertSame('PARTICIPANTS_ADDED', $make[3]);
        $this->assertSame($json, [
            [
                'owner_id' => $this->tippin->getKey(),
                'owner_type' => $this->tippin->getMorphClass(),
            ],
            [
                'owner_id' => $this->developers->getKey(),
                'owner_type' => $this->developers->getMorphClass(),
            ]
        ]);
    }
}
