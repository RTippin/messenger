<?php

namespace RTippin\Messenger\Tests\Support;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\MessageTransformer;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\UserModel;

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
            Message::PARTICIPANT_JOINED_WITH_INVITE,
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
            Message::VIDEO_CALL,
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
            Message::GROUP_AVATAR_CHANGED,
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
            Message::THREAD_ARCHIVED,
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
            Message::THREAD_ARCHIVED,
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
            Message::GROUP_CREATED,
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
            Message::GROUP_RENAMED,
        ], MessageTransformer::makeGroupRenamed($thread, $this->tippin, 'Renamed'));
    }

    /** @test */
    public function it_makes_participant_demoted()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();

        $make = MessageTransformer::makeParticipantDemoted($thread, $this->tippin, $participant);
        $json = MessageTransformer::decodeBodyJson($make[2]);

        $this->assertSame($thread, $make[0]);
        $this->assertSame($this->tippin, $make[1]);
        $this->assertSame(Message::DEMOTED_ADMIN, $make[3]);
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
        $json = MessageTransformer::decodeBodyJson($make[2]);

        $this->assertSame($thread, $make[0]);
        $this->assertSame($this->tippin, $make[1]);
        $this->assertSame(Message::PROMOTED_ADMIN, $make[3]);
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
            Message::PARTICIPANT_LEFT_GROUP,
        ], MessageTransformer::makeGroupLeft($thread, $this->tippin));
    }

    /** @test */
    public function it_makes_removed_from_group()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();

        $make = MessageTransformer::makeRemovedFromGroup($thread, $this->tippin, $participant);
        $json = MessageTransformer::decodeBodyJson($make[2]);

        $this->assertSame($thread, $make[0]);
        $this->assertSame($this->tippin, $make[1]);
        $this->assertSame(Message::PARTICIPANT_REMOVED, $make[3]);
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
        $json = MessageTransformer::decodeBodyJson($make[2]);

        $this->assertSame($thread, $make[0]);
        $this->assertSame($this->tippin, $make[1]);
        $this->assertSame(Message::PARTICIPANTS_ADDED, $make[3]);
        $this->assertSame($json, [
            [
                'owner_id' => $this->tippin->getKey(),
                'owner_type' => $this->tippin->getMorphClass(),
            ],
            [
                'owner_id' => $this->developers->getKey(),
                'owner_type' => $this->developers->getMorphClass(),
            ],
        ]);
    }

    /** @test */
    public function it_makes_bot_added()
    {
        $thread = Thread::factory()->group()->create();

        $this->assertSame([
            $thread,
            $this->tippin,
            'added the BOT - Test Bot',
            Message::BOT_ADDED,
        ], MessageTransformer::makeBotAdded($thread, $this->tippin, 'Test Bot'));
    }

    /** @test */
    public function it_makes_bot_renamed()
    {
        $thread = Thread::factory()->group()->create();

        $this->assertSame([
            $thread,
            $this->tippin,
            'renamed the BOT ( Test Bot ) to Renamed',
            Message::BOT_RENAMED,
        ], MessageTransformer::makeBotRenamed($thread, $this->tippin, 'Test Bot', 'Renamed'));
    }

    /** @test */
    public function it_makes_bot_avatar_changed()
    {
        $thread = Thread::factory()->group()->create();

        $this->assertSame([
            $thread,
            $this->tippin,
            'updated the avatar for the BOT - Test Bot',
            Message::BOT_AVATAR_CHANGED,
        ], MessageTransformer::makeBotAvatarChanged($thread, $this->tippin, 'Test Bot'));
    }

    /** @test */
    public function it_makes_bot_removed()
    {
        $thread = Thread::factory()->group()->create();

        $this->assertSame([
            $thread,
            $this->tippin,
            'removed the BOT - Test Bot',
            Message::BOT_REMOVED,
        ], MessageTransformer::makeBotRemoved($thread, $this->tippin, 'Test Bot'));
    }

    /** @test */
    public function it_makes_bot_package_installed()
    {
        $thread = Thread::factory()->group()->create();

        $this->assertSame([
            $thread,
            $this->tippin,
            'installed the BOT - Test Bot',
            Message::BOT_PACKAGE_INSTALLED,
        ], MessageTransformer::makeBotPackageInstalled($thread, $this->tippin, 'Test Bot'));
    }

    /** @test */
    public function it_locates_content_owner_with_current_participant()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $owner = MessageTransformer::locateContentOwner($message, [
            'owner_id' => $participant->owner_id,
            'owner_type' => $participant->owner_type,
        ]);

        $this->assertEquals($this->tippin->getKey(), $owner->getKey());
        $this->assertSame($this->tippin->getMorphClass(), $owner->getMorphClass());
        $this->assertInstanceOf(UserModel::class, $owner);
    }

    /** @test */
    public function it_locates_content_owner_with_non_participant()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $owner = MessageTransformer::locateContentOwner($message, [
            'owner_id' => $this->developers->getKey(),
            'owner_type' => $this->developers->getMorphClass(),
        ]);

        $this->assertEquals($this->developers->getKey(), $owner->getKey());
        $this->assertSame($this->developers->getMorphClass(), $owner->getMorphClass());
        $this->assertInstanceOf(CompanyModel::class, $owner);
    }

    /** @test */
    public function it_returns_ghost_user_when_invalid_provider()
    {
        Messenger::registerProviders([UserModel::class], true);
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->developers)->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $owner = MessageTransformer::locateContentOwner($message, [
            'owner_id' => $participant->owner_id,
            'owner_type' => $participant->owner_type,
        ]);

        $this->assertInstanceOf(GhostUser::class, $owner);
    }

    /** @test */
    public function it_returns_ghost_user_when_provider_not_found()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $owner = MessageTransformer::locateContentOwner($message, [
            'owner_id' => 404,
            'owner_type' => $participant->owner_type,
        ]);

        $this->assertInstanceOf(GhostUser::class, $owner);
    }

    /** @test */
    public function it_returns_ghost_user_when_no_body_json()
    {
        $message = Message::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        $owner = MessageTransformer::locateContentOwner($message, null);

        $this->assertInstanceOf(GhostUser::class, $owner);
    }

    /** @test */
    public function it_transforms_and_sanitizes_html_body()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '&"<>']);
        $image = Message::factory()->for($thread)->owner($this->tippin)->image()->create();
        $document = Message::factory()->for($thread)->owner($this->tippin)->document()->create();
        $audio = Message::factory()->for($thread)->owner($this->tippin)->audio()->create();

        $this->assertSame('&amp;&quot;&lt;&gt;', MessageTransformer::transform($message));
        $this->assertSame('picture.jpg', MessageTransformer::transform($image));
        $this->assertSame('document.pdf', MessageTransformer::transform($document));
        $this->assertSame('sound.mp3', MessageTransformer::transform($audio));
    }

    /** @test */
    public function it_transforms_joined_with_invite()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::PARTICIPANT_JOINED_WITH_INVITE,
                'body' => MessageTransformer::makeJoinedWithInvite($thread, $this->tippin)[2],
            ]);

        $this->assertSame('joined', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_video_call()
    {
        $thread = Thread::factory()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::VIDEO_CALL,
                'body' => MessageTransformer::makeVideoCall($thread, $this->tippin, $call)[2],
            ]);

        $this->assertSame('was in a video call', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_video_call_without_body_json()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::VIDEO_CALL,
                'body' => '',
            ]);

        $this->assertSame('was in a video call', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_video_call_with_one_participant()
    {
        $thread = Thread::factory()->create();
        $call = $this->createCall($thread, $this->tippin);
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::VIDEO_CALL,
                'body' => MessageTransformer::makeVideoCall($thread, $this->tippin, $call)[2],
            ]);

        $this->assertSame('was in a video call', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_video_call_with_two_participants()
    {
        $thread = Thread::factory()->create();
        $call = $this->createCall($thread, $this->tippin, $this->doe);
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::VIDEO_CALL,
                'body' => MessageTransformer::makeVideoCall($thread, $this->tippin, $call)[2],
            ]);

        $this->assertSame('was in a video call with John Doe', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_video_call_with_missing_recipient()
    {
        $thread = Thread::factory()->create();
        $call = $this->createCall($thread, $this->tippin, $this->doe);
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::VIDEO_CALL,
                'body' => MessageTransformer::makeVideoCall($thread, $this->tippin, $call)[2],
            ]);
        $this->doe->delete();

        $this->assertSame('was in a video call with Ghost Profile', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_video_call_with_missing_owner()
    {
        $thread = Thread::factory()->create();
        $call = $this->createCall($thread, $this->doe, $this->tippin);
        $message = Message::factory()
            ->for($thread)
            ->owner($this->doe)
            ->create([
                'type' => Message::VIDEO_CALL,
                'body' => MessageTransformer::makeVideoCall($thread, $this->tippin, $call)[2],
            ]);
        $this->doe->delete();

        $this->assertSame('was in a video call with Richard Tippin', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_video_call_with_three_participants()
    {
        $thread = Thread::factory()->group()->create();
        $call = $this->createCall($thread, $this->tippin, $this->doe, $this->developers);
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::VIDEO_CALL,
                'body' => MessageTransformer::makeVideoCall($thread, $this->tippin, $call)[2],
            ]);

        $this->assertSame('was in a video call with John Doe and Developers', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_video_call_with_four_participants()
    {
        $thread = Thread::factory()->group()->create();
        $call = $this->createCall($thread, $this->tippin, $this->doe, $this->developers, $this->createJaneSmith());
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::VIDEO_CALL,
                'body' => MessageTransformer::makeVideoCall($thread, $this->tippin, $call)[2],
            ]);

        $this->assertSame('was in a video call with John Doe, Developers, and Jane Smith', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_video_call_with_five_participants()
    {
        $thread = Thread::factory()->group()->create();
        $call = $this->createCall(
            $thread,
            $this->tippin,
            $this->doe,
            $this->developers,
            $this->createJaneSmith(),
            $this->createSomeCompany()
        );
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::VIDEO_CALL,
                'body' => MessageTransformer::makeVideoCall($thread, $this->tippin, $call)[2],
            ]);

        $this->assertSame('was in a video call with John Doe, Developers, Jane Smith, and 1 others', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_video_call_with_many_participants()
    {
        $thread = Thread::factory()->group()->create();
        $call = $this->createCall(
            $thread,
            $this->tippin,
            $this->doe,
            $this->developers,
            $this->createJaneSmith(),
            $this->createSomeCompany(),
            UserModel::factory()->create(),
            UserModel::factory()->create()
        );
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::VIDEO_CALL,
                'body' => MessageTransformer::makeVideoCall($thread, $this->tippin, $call)[2],
            ]);

        $this->assertSame('was in a video call with John Doe, Developers, Jane Smith, and 3 others', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_group_avatar_changed()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::GROUP_AVATAR_CHANGED,
                'body' => MessageTransformer::makeGroupAvatarChanged($thread, $this->tippin)[2],
            ]);

        $this->assertSame('updated the avatar', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_private_thread_archived()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::THREAD_ARCHIVED,
                'body' => MessageTransformer::makeThreadArchived($thread, $this->tippin)[2],
            ]);

        $this->assertSame('archived the conversation', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_group_thread_archived()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::THREAD_ARCHIVED,
                'body' => MessageTransformer::makeThreadArchived($thread, $this->tippin)[2],
            ]);

        $this->assertSame('archived the group', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_group_created()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::GROUP_CREATED,
                'body' => MessageTransformer::makeGroupCreated($thread, $this->tippin, 'Some Group')[2],
            ]);

        $this->assertSame('created Some Group', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_group_renamed()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::GROUP_RENAMED,
                'body' => MessageTransformer::makeGroupRenamed($thread, $this->tippin, 'Some Group')[2],
            ]);

        $this->assertSame('renamed the group to Some Group', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_demoted_admin()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::DEMOTED_ADMIN,
                'body' => MessageTransformer::makeParticipantDemoted($thread, $this->tippin, $participant)[2],
            ]);

        $this->assertSame('demoted John Doe', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_demoted_admin_without_body_json()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::DEMOTED_ADMIN,
                'body' => '',
            ]);

        $this->assertSame('demoted Ghost Profile', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_promoted_admin()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::PROMOTED_ADMIN,
                'body' => MessageTransformer::makeParticipantPromoted($thread, $this->tippin, $participant)[2],
            ]);

        $this->assertSame('promoted John Doe', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_promoted_admin_without_body_json()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::PROMOTED_ADMIN,
                'body' => '',
            ]);

        $this->assertSame('promoted Ghost Profile', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_participant_left()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::PARTICIPANT_LEFT_GROUP,
                'body' => MessageTransformer::makeGroupLeft($thread, $this->tippin)[2],
            ]);

        $this->assertSame('left', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_participant_removed()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::PARTICIPANT_REMOVED,
                'body' => MessageTransformer::makeRemovedFromGroup($thread, $this->tippin, $participant)[2],
            ]);

        $this->assertSame('removed John Doe', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_participant_removed_without_body_json()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::PARTICIPANT_REMOVED,
                'body' => '',
            ]);

        $this->assertSame('removed Ghost Profile', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_one_participant_added()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::PARTICIPANTS_ADDED,
                'body' => MessageTransformer::makeParticipantsAdded($thread, $this->tippin, collect([$participant]))[2],
            ]);

        $this->assertSame('added John Doe', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_participants_added_without_body_json()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::PARTICIPANTS_ADDED,
                'body' => '',
            ]);

        $this->assertSame('added participants', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_two_participants_added()
    {
        $thread = Thread::factory()->group()->create();
        $participant1 = Participant::factory()->for($thread)->owner($this->doe)->create();
        $participant2 = Participant::factory()->for($thread)->owner($this->developers)->create();
        $participants = collect([$participant1, $participant2]);
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::PARTICIPANTS_ADDED,
                'body' => MessageTransformer::makeParticipantsAdded($thread, $this->tippin, $participants)[2],
            ]);

        $this->assertSame('added John Doe and Developers', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_participants_added_with_missing_participant()
    {
        $thread = Thread::factory()->group()->create();
        $participant1 = Participant::factory()->for($thread)->owner($this->doe)->create();
        $participant2 = Participant::factory()->for($thread)->owner($this->developers)->create();
        $participants = collect([$participant1, $participant2]);
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::PARTICIPANTS_ADDED,
                'body' => MessageTransformer::makeParticipantsAdded($thread, $this->tippin, $participants)[2],
            ]);
        $this->doe->delete();

        $this->assertSame('added Ghost Profile and Developers', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_three_participants_added()
    {
        $thread = Thread::factory()->group()->create();
        $participant1 = Participant::factory()->for($thread)->owner($this->doe)->create();
        $participant2 = Participant::factory()->for($thread)->owner($this->developers)->create();
        $participant3 = Participant::factory()->for($thread)->owner($this->createJaneSmith())->create();
        $participants = collect([$participant1, $participant2, $participant3]);
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::PARTICIPANTS_ADDED,
                'body' => MessageTransformer::makeParticipantsAdded($thread, $this->tippin, $participants)[2],
            ]);

        $this->assertSame('added John Doe, Developers, and Jane Smith', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_four_participants_added()
    {
        $thread = Thread::factory()->group()->create();
        $participant1 = Participant::factory()->for($thread)->owner($this->doe)->create();
        $participant2 = Participant::factory()->for($thread)->owner($this->developers)->create();
        $participant3 = Participant::factory()->for($thread)->owner($this->createJaneSmith())->create();
        $participant4 = Participant::factory()->for($thread)->owner($this->createSomeCompany())->create();
        $participants = collect([$participant1, $participant2, $participant3, $participant4]);
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::PARTICIPANTS_ADDED,
                'body' => MessageTransformer::makeParticipantsAdded($thread, $this->tippin, $participants)[2],
            ]);

        $this->assertSame('added John Doe, Developers, Jane Smith, and 1 others', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_many_participants_added()
    {
        $thread = Thread::factory()->group()->create();
        $participant1 = Participant::factory()->for($thread)->owner($this->doe)->create();
        $participant2 = Participant::factory()->for($thread)->owner($this->developers)->create();
        $participant3 = Participant::factory()->for($thread)->owner($this->createJaneSmith())->create();
        $participant4 = Participant::factory()->for($thread)->owner($this->createSomeCompany())->create();
        $participant5 = Participant::factory()->for($thread)->owner(UserModel::factory()->create())->create();
        $participant6 = Participant::factory()->for($thread)->owner(UserModel::factory()->create())->create();
        $participants = collect([$participant1, $participant2, $participant3, $participant4, $participant5, $participant6]);
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::PARTICIPANTS_ADDED,
                'body' => MessageTransformer::makeParticipantsAdded($thread, $this->tippin, $participants)[2],
            ]);

        $this->assertSame('added John Doe, Developers, Jane Smith, and 3 others', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_bot_added()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::BOT_ADDED,
                'body' => MessageTransformer::makeBotAdded($thread, $this->tippin, 'Test Bot')[2],
            ]);

        $this->assertSame('added the BOT - Test Bot', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_bot_renamed()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::BOT_RENAMED,
                'body' => MessageTransformer::makeBotRenamed($thread, $this->tippin, 'Test Bot', 'Renamed')[2],
            ]);

        $this->assertSame('renamed the BOT ( Test Bot ) to Renamed', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_bot_avatar_changed()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::BOT_AVATAR_CHANGED,
                'body' => MessageTransformer::makeBotAvatarChanged($thread, $this->tippin, 'Test Bot')[2],
            ]);

        $this->assertSame('updated the avatar for the BOT - Test Bot', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_bot_removed()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::BOT_REMOVED,
                'body' => MessageTransformer::makeBotRemoved($thread, $this->tippin, 'Test Bot')[2],
            ]);

        $this->assertSame('removed the BOT - Test Bot', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_bot_package_installed()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'type' => Message::BOT_PACKAGE_INSTALLED,
                'body' => MessageTransformer::makeBotPackageInstalled($thread, $this->tippin, 'Test Bot')[2],
            ]);

        $this->assertSame('installed the BOT - Test Bot', MessageTransformer::transform($message));
    }

    /** @test */
    public function it_transforms_null_message()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->create([
                'body' => null,
            ]);

        $this->assertSame('', MessageTransformer::transform($message));
    }
}
