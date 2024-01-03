<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\UserModel;

class ThreadTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $private = Thread::factory()->create();
        $group = Thread::factory()->group()->create();

        $this->assertDatabaseCount('threads', 2);
        $this->assertDatabaseHas('threads', [
            'id' => $private->id,
        ]);
        $this->assertDatabaseHas('threads', [
            'id' => $group->id,
        ]);
        $this->assertInstanceOf(Thread::class, $private);
        $this->assertSame(1, Thread::private()->count());
        $this->assertSame(1, Thread::group()->count());
    }

    /** @test */
    public function it_cast_attributes()
    {
        $thread = Thread::factory()->group()->trashed()->create(['subject' => 'Test']);

        $this->assertInstanceOf(Carbon::class, $thread->created_at);
        $this->assertInstanceOf(Carbon::class, $thread->updated_at);
        $this->assertInstanceOf(Carbon::class, $thread->deleted_at);
        $this->assertTrue($thread->add_participants);
        $this->assertTrue($thread->invitations);
        $this->assertTrue($thread->calling);
        $this->assertTrue($thread->messaging);
        $this->assertTrue($thread->knocks);
        $this->assertTrue($thread->chat_bots);
        $this->assertSame(2, $thread->type);
        $this->assertSame('Test', $thread->subject);
    }

    /** @test */
    public function it_has_relations()
    {
        $thread = Thread::factory()->group()->create();

        $this->assertInstanceOf(Collection::class, $thread->participants);
        $this->assertInstanceOf(Collection::class, $thread->messages);
        $this->assertInstanceOf(Collection::class, $thread->audio);
        $this->assertInstanceOf(Collection::class, $thread->documents);
        $this->assertInstanceOf(Collection::class, $thread->images);
        $this->assertInstanceOf(Collection::class, $thread->logs);
        $this->assertInstanceOf(Collection::class, $thread->videos);
        $this->assertInstanceOf(Collection::class, $thread->calls);
        $this->assertInstanceOf(Collection::class, $thread->invites);
        $this->assertInstanceOf(Collection::class, $thread->bots);
    }

    /** @test */
    public function it_has_active_call()
    {
        $thread = Thread::factory()->create();
        Call::factory()->for($thread)->owner($this->tippin)->setup()->create();

        $this->assertInstanceOf(Call::class, $thread->activeCall);
        $this->assertSame($thread->id, $thread->activeCall->thread_id);
        $this->assertTrue($thread->hasActiveCall());
    }

    /** @test */
    public function it_doesnt_have_active_call()
    {
        $thread = Thread::factory()->create();

        $this->assertNull($thread->activeCall);
        $this->assertFalse($thread->hasActiveCall());
    }

    /** @test */
    public function it_has_latest_message()
    {
        $thread = Thread::factory()->create();
        Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->travel(5)->minutes();
        $latest = Message::factory()->for($thread)->owner($this->tippin)->create();

        $this->assertInstanceOf(Message::class, $thread->latestMessage);
        $this->assertSame($thread->id, $thread->latestMessage->thread_id);
        $this->assertSame($latest->id, $thread->latestMessage->id);
    }

    /** @test */
    public function it_doesnt_have_have_latest_message()
    {
        $thread = Thread::factory()->create();

        $this->assertNull($thread->latestMessage);
    }

    /** @test */
    public function it_has_presence_channel()
    {
        $thread = Thread::factory()->create();

        $this->assertSame('thread.'.$thread->id, $thread->getPresenceChannel());
    }

    /** @test */
    public function it_has_types()
    {
        $private = Thread::factory()->create();
        $group = Thread::factory()->group()->create();

        $this->assertTrue($private->isPrivate());
        $this->assertFalse($private->isGroup());
        $this->assertTrue($group->isGroup());
        $this->assertFalse($group->isPrivate());
        $this->assertSame('PRIVATE', $private->getTypeVerbose());
        $this->assertSame('GROUP', $group->getTypeVerbose());
    }

    /** @test */
    public function it_has_storage()
    {
        $thread = Thread::factory()->group()->create(['image' => 'test.png']);

        $this->assertSame('messenger', $thread->getStorageDisk());
        $this->assertSame("threads/$thread->id", $thread->getStorageDirectory());
        $this->assertSame("threads/$thread->id/avatar/test.png", $thread->getAvatarPath());
        $this->assertSame("threads/$thread->id/images", $thread->getImagesDirectory());
        $this->assertSame("threads/$thread->id/documents", $thread->getDocumentsDirectory());
        $this->assertSame("threads/$thread->id/audio", $thread->getAudioDirectory());
        $this->assertSame("threads/$thread->id/videos", $thread->getVideoDirectory());
        $this->assertSame("threads/$thread->id/avatar", $thread->getAvatarDirectory());
    }

    /** @test */
    public function it_doesnt_have_current_participant_if_provider_not_set()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->assertNull($thread->currentParticipant());
        $this->assertFalse($thread->hasCurrentProvider());
    }

    /** @test */
    public function it_doesnt_have_current_participant_when_not_in_thread()
    {
        Messenger::setProvider($this->developers);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->assertNull($thread->currentParticipant());
        $this->assertFalse($thread->hasCurrentProvider());
    }

    /** @test */
    public function it_has_current_participant()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();

        $this->assertInstanceOf(Participant::class, $thread->currentParticipant());
        $this->assertTrue($thread->hasCurrentProvider());
        $this->assertSame($participant->id, $thread->currentParticipant()->id);
    }

    /** @test */
    public function recipient_returns_ghost_participant_if_group_thread()
    {
        Messenger::setProvider($this->tippin);
        $thread = $this->createGroupThread($this->tippin, $this->doe);

        $this->assertInstanceOf(Participant::class, $thread->recipient());
        $this->assertInstanceOf(GhostUser::class, $thread->recipient()->owner);
        $this->assertSame($thread->id, $thread->recipient()->thread_id);
        $this->assertNull($thread->recipient()->owner_id);
    }

    /** @test */
    public function recipient_returns_ghost_participant_if_not_in_private_thread()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);
        Messenger::setProvider($this->developers);

        $this->assertInstanceOf(Participant::class, $private->recipient());
        $this->assertInstanceOf(GhostUser::class, $private->recipient()->owner);
        $this->assertSame($private->id, $private->recipient()->thread_id);
        $this->assertNull($private->recipient()->owner_id);
    }

    /** @test */
    public function recipient_returns_other_participant_in_private_thread()
    {
        Messenger::setProvider($this->tippin);
        $private = Thread::factory()->create();
        Participant::factory()->for($private)->owner($this->tippin)->create();
        $recipient = Participant::factory()->for($private)->owner($this->doe)->create();

        $this->assertInstanceOf(Participant::class, $private->recipient());
        $this->assertInstanceOf(MessengerProvider::class, $private->recipient()->owner);
        $this->assertSame($private->id, $private->recipient()->thread_id);
        $this->assertSame($recipient->id, $private->recipient()->id);
    }

    /** @test */
    public function recipient_returns_ghost_participant_if_invalid_recipient()
    {
        Messenger::registerProviders([UserModel::class], true);
        Messenger::setProvider($this->tippin);
        $private = $this->createPrivateThread($this->tippin, $this->developers);

        $this->assertInstanceOf(Participant::class, $private->recipient());
        $this->assertInstanceOf(GhostUser::class, $private->recipient()->owner);
        $this->assertSame($private->id, $private->recipient()->thread_id);
        $this->assertNull($private->recipient()->owner_id);
    }

    /** @test */
    public function it_has_name_depending_on_type()
    {
        Messenger::setProvider($this->tippin);
        $group = Thread::factory()->group()->create(['subject' => 'Test']);
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->assertSame('Test', $group->name());
        $this->assertSame('John Doe', $private->name());
    }

    /** @test */
    public function it_has_avatar_depending_on_type()
    {
        Messenger::setProvider($this->tippin);
        $group = Thread::factory()->group()->create(['image' => 'test.png']);
        $private = $this->createPrivateThread($this->tippin, $this->doe);
        $groupAvatar = [
            'sm' => "/messenger/assets/threads/$group->id/avatar/sm/test.png",
            'md' => "/messenger/assets/threads/$group->id/avatar/md/test.png",
            'lg' => "/messenger/assets/threads/$group->id/avatar/lg/test.png",
        ];
        $privateAvatar = [
            'sm' => "/messenger/assets/provider/user/{$this->doe->getKey()}/sm/default.png",
            'md' => "/messenger/assets/provider/user/{$this->doe->getKey()}/md/default.png",
            'lg' => "/messenger/assets/provider/user/{$this->doe->getKey()}/lg/default.png",
        ];

        $this->assertSame($groupAvatar, $group->threadAvatar());
        $this->assertSame($privateAvatar, $private->threadAvatar());
    }

    /** @test */
    public function is_admin()
    {
        Messenger::setProvider($this->tippin);
        $thread = $this->createGroupThread($this->tippin);

        $this->assertTrue($thread->isAdmin());
    }

    /** @test */
    public function is_not_admin()
    {
        Messenger::setProvider($this->doe);
        $thread = $this->createGroupThread($this->tippin, $this->doe);

        $this->assertFalse($thread->isAdmin());
    }

    /** @test */
    public function private_has_no_admin()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);
        Messenger::setProvider($this->tippin);

        $this->assertFalse($private->isAdmin());
    }

    /** @test */
    public function not_locked()
    {
        $group = $this->createGroupThread($this->tippin);
        $private = $this->createPrivateThread($this->tippin, $this->doe);
        Messenger::setProvider($this->tippin);

        $this->assertFalse($private->isLocked());
        $this->assertFalse($group->isLocked());
    }

    /** @test */
    public function is_locked_if_not_participant()
    {
        $group = $this->createGroupThread($this->tippin);
        $private = $this->createPrivateThread($this->tippin, $this->doe);
        Messenger::setProvider($this->developers);

        $this->assertTrue($private->isLocked());
        $this->assertTrue($group->isLocked());
    }

    /** @test */
    public function is_locked_when_set_in_thread_settings()
    {
        $group = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($group)->owner($this->tippin)->admin()->create();
        $private = Thread::factory()->locked()->create();
        Participant::factory()->for($private)->owner($this->tippin)->create();
        Participant::factory()->for($private)->owner($this->doe)->create();
        Messenger::setProvider($this->tippin);

        $this->assertTrue($private->isLocked());
        $this->assertTrue($group->isLocked());
    }

    /** @test */
    public function private_locked_if_recipient_not_found()
    {
        Messenger::setProvider($this->tippin);
        $private = Thread::factory()->create();
        Participant::factory()->for($private)->owner($this->tippin)->create();
        Participant::factory()->for($private)->owner($this->doe)->trashed()->create();

        $this->assertTrue($private->isLocked());
    }

    /** @test */
    public function is_not_muted()
    {
        $thread = $this->createGroupThread($this->tippin);
        Messenger::setProvider($this->tippin);

        $this->assertFalse($thread->isMuted());
    }

    /** @test */
    public function is_muted()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->muted()->create();

        $this->assertTrue($thread->isMuted());
    }

    /** @test */
    public function group_is_not_pending()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->pending()->create();

        $this->assertFalse($thread->isPending());
    }

    /** @test */
    public function private_is_pending_and_awaiting_approval_if_provider_pending()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->pending()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();

        $this->assertTrue($thread->isPending());
        $this->assertTrue($thread->isAwaitingMyApproval());
    }

    /** @test */
    public function private_is_pending_for_other_participant_not_awaiting_their_approval()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();

        $this->assertTrue($thread->isPending());
        $this->assertFalse($thread->isAwaitingMyApproval());
    }

    /** @test */
    public function can_message()
    {
        Messenger::setProvider($this->tippin);
        $thread = $this->createGroupThread($this->tippin);

        $this->assertTrue($thread->canMessage());
        $this->assertTrue($thread->canMessage());
    }

    /** @test */
    public function cannot_message_if_thread_locked()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();

        $this->assertFalse($thread->canMessage());
    }

    /** @test */
    public function cannot_message_if_disabled_in_thread_settings()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create(['messaging' => false]);
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();

        $this->assertFalse($thread->canMessage());
    }

    /** @test */
    public function cannot_message_if_awaiting_approval()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->pending()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();

        $this->assertFalse($thread->canMessage());
    }

    /** @test */
    public function can_message_if_awaiting_other_participant_approval()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();

        $this->assertTrue($thread->canMessage());
    }

    /** @test */
    public function can_message_if_disabled_on_participant_but_is_admin()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create(['send_messages' => false]);

        $this->assertTrue($thread->canMessage());
    }

    /** @test */
    public function cannot_message_if_participant_disabled_and_not_admin()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create(['send_messages' => false]);

        $this->assertFalse($thread->canMessage());
    }

    /** @test */
    public function can_add_participants()
    {
        Messenger::setProvider($this->tippin);
        $thread = $this->createGroupThread($this->tippin);

        $this->assertTrue($thread->canAddParticipants());
    }

    /** @test */
    public function cannot_add_participants_if_thread_locked()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();

        $this->assertFalse($thread->canAddParticipants());
    }

    /** @test */
    public function cannot_add_participants_if_disabled_on_thread_settings()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create(['add_participants' => false]);
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();

        $this->assertFalse($thread->canAddParticipants());
    }

    /** @test */
    public function can_add_participants_if_participant_disabled_but_is_admin()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create(['add_participants' => false]);

        $this->assertTrue($thread->canAddParticipants());
    }

    /** @test */
    public function non_admin_without_permission_cannot_add_participants()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();

        $this->assertFalse($thread->canAddParticipants());
    }

    /** @test */
    public function non_admin_with_permission_can_add_participants()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create(['add_participants' => true]);

        $this->assertTrue($thread->canAddParticipants());
    }

    /** @test */
    public function cannot_add_participants_to_private_thread()
    {
        Messenger::setProvider($this->tippin);
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->assertFalse($private->canAddParticipants());
    }

    /** @test */
    public function cannot_invite_participants_to_private_thread()
    {
        Messenger::setProvider($this->tippin);
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->assertFalse($private->canInviteParticipants());
    }

    /** @test */
    public function admin_can_invite_participants()
    {
        Messenger::setProvider($this->tippin);
        $thread = $this->createGroupThread($this->tippin);

        $this->assertTrue($thread->canInviteParticipants());
    }

    /** @test */
    public function non_admin_without_permission_cannot_invite_participants()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();

        $this->assertFalse($thread->canInviteParticipants());
    }

    /** @test */
    public function non_admin_with_permission_can_invite_participants()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create(['manage_invites' => true]);

        $this->assertTrue($thread->canInviteParticipants());
    }

    /** @test */
    public function can_invite_participants_if_participant_disabled_but_is_admin()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create(['manage_invites' => false]);

        $this->assertTrue($thread->canInviteParticipants());
    }

    /** @test */
    public function cannot_invite_participants_if_disabled_in_thread_settings()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create(['invitations' => false]);
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();

        $this->assertFalse($thread->canInviteParticipants());
    }

    /** @test */
    public function cannot_invite_participants_if_thread_locked()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();

        $this->assertFalse($thread->canInviteParticipants());
    }

    /** @test */
    public function cannot_invite_participants_if_disabled_in_config()
    {
        Messenger::setThreadInvites(false);
        Messenger::setProvider($this->tippin);
        $thread = $this->createGroupThread($this->tippin);

        $this->assertFalse($thread->canInviteParticipants());
    }

    /** @test */
    public function can_join_with_invite()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();

        $this->assertTrue($thread->canJoinWithInvite());
    }

    /** @test */
    public function cannot_join_with_invite_if_no_provider_set()
    {
        $thread = Thread::factory()->group()->create();

        $this->assertFalse($thread->canJoinWithInvite());
    }

    /** @test */
    public function cannot_join_with_invite_if_disabled_in_config()
    {
        Messenger::setThreadInvites(false);
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();

        $this->assertFalse($thread->canJoinWithInvite());
    }

    /** @test */
    public function cannot_join_with_invite_if_already_in_thread()
    {
        Messenger::setProvider($this->tippin);
        $thread = $this->createGroupThread($this->tippin);

        $this->assertFalse($thread->canJoinWithInvite());
    }

    /** @test */
    public function cannot_join_with_invite_if_thread_locked()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->locked()->create();

        $this->assertFalse($thread->canJoinWithInvite());
    }

    /** @test */
    public function cannot_join_with_invite_if_disabled_in_thread_settings()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create(['invitations' => false]);

        $this->assertFalse($thread->canJoinWithInvite());
    }

    /** @test */
    public function cannot_join_with_invite_if_in_private_thread()
    {
        Messenger::setProvider($this->developers);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->assertFalse($thread->canJoinWithInvite());
    }

    /** @test */
    public function can_start_call()
    {
        Messenger::setProvider($this->tippin);
        $private = $this->createPrivateThread($this->tippin, $this->doe);
        $group = $this->createGroupThread($this->tippin);

        $this->assertTrue($private->canCall());
        $this->assertTrue($group->canCall());
    }

    /** @test */
    public function can_start_call_if_participant_disabled_but_is_admin()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create(['start_calls' => false]);

        $this->assertTrue($thread->canCall());
    }

    /** @test */
    public function can_start_call_if_participant_disabled_but_is_private_thread()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create(['start_calls' => false]);
        Participant::factory()->for($thread)->owner($this->doe)->create();

        $this->assertTrue($thread->canCall());
    }

    /** @test */
    public function cannot_start_call_if_private_thread_is_pending()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();

        $this->assertFalse($thread->canCall());
    }

    /** @test */
    public function non_admin_without_permissions_cannot_start_call()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();

        $this->assertFalse($thread->canCall());
    }

    /** @test */
    public function cannot_start_call_if_disabled_in_config()
    {
        Messenger::setCalling(false);
        Messenger::setProvider($this->tippin);
        $thread = $this->createGroupThread($this->tippin);

        $this->assertFalse($thread->canCall());
    }

    /** @test */
    public function cannot_start_call_if_temporarily_disabled()
    {
        Messenger::disableCallsTemporarily(1);
        Messenger::setProvider($this->tippin);
        $thread = $this->createGroupThread($this->tippin);

        $this->assertFalse($thread->canCall());
    }

    /** @test */
    public function cannot_start_call_if_thread_locked()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();

        $this->assertFalse($thread->canCall());
    }

    /** @test */
    public function cannot_start_call_if_disabled_in_thread_settings()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create(['calling' => false]);
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();

        $this->assertFalse($thread->canCall());
    }

    /** @test */
    public function can_knock()
    {
        Messenger::setProvider($this->tippin);
        $private = $this->createPrivateThread($this->tippin, $this->doe);
        $group = $this->createGroupThread($this->tippin);

        $this->assertTrue($private->canKnock());
        $this->assertTrue($group->canKnock());
    }

    /** @test */
    public function can_knock_if_participant_disabled_but_is_admin()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create(['send_knocks' => false]);

        $this->assertTrue($thread->canKnock());
    }

    /** @test */
    public function can_knock_if_participant_disabled_but_is_private_thread()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create(['send_knocks' => false]);
        Participant::factory()->for($thread)->owner($this->doe)->create();

        $this->assertTrue($thread->canKnock());
    }

    /** @test */
    public function cannot_knock_if_private_thread_is_pending()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();

        $this->assertFalse($thread->canKnock());
    }

    /** @test */
    public function non_admin_without_permissions_cannot_knock()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();

        $this->assertFalse($thread->canKnock());
    }

    /** @test */
    public function cannot_knock_if_disabled_in_config()
    {
        Messenger::setKnockKnock(false);
        Messenger::setProvider($this->tippin);
        $thread = $this->createGroupThread($this->tippin);

        $this->assertFalse($thread->canKnock());
    }

    /** @test */
    public function cannot_knock_if_thread_locked()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();

        $this->assertFalse($thread->canKnock());
    }

    /** @test */
    public function cannot_knock_if_disabled_in_thread_settings()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create(['knocks' => false]);
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();

        $this->assertFalse($thread->canKnock());
    }

    /** @test */
    public function is_unread_if_last_read_null()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();

        $this->assertTrue($thread->isUnread());
    }

    /** @test */
    public function is_unread_if_last_read_less_than_thread_last_updated()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create(['updated_at' => now()->addHour()]);
        Participant::factory()->for($thread)->owner($this->tippin)->read()->create();

        $this->assertTrue($thread->isUnread());
    }

    /** @test */
    public function not_unread_if_last_read_greater_than_thread_last_updated()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create(['updated_at' => now()->subHour()]);
        Participant::factory()->for($thread)->owner($this->tippin)->read()->create();

        $this->assertFalse($thread->isUnread());
    }

    /** @test */
    public function not_unread_if_provider_not_set()
    {
        $thread = Thread::factory()->group()->create();

        $this->assertFalse($thread->isUnread());
    }

    /** @test */
    public function not_unread_if_participant_not_in_thread()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();

        $this->assertFalse($thread->isUnread());
    }

    /** @test */
    public function unread_count_matches_message_count_if_last_read_null()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Message::factory()->for($thread)->owner($this->doe)->count(3)->create();

        $this->assertSame(3, $thread->unreadCount());
    }

    /** @test */
    public function unread_count_matches_message_count_created_after_last_read()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Message::factory()->for($thread)->owner($this->doe)->count(3)->create();
        Participant::factory()->for($thread)->owner($this->tippin)->read()->create();
        $this->travel(1)->minutes();
        Message::factory()->for($thread)->owner($this->doe)->count(2)->create();
        $thread->touch();

        $this->assertSame(2, $thread->unreadCount());
    }

    /** @test */
    public function unread_count_zero_if_no_messages()
    {
        Messenger::setProvider($this->tippin);
        $thread = $this->createGroupThread($this->tippin);

        $this->assertSame(0, $thread->unreadCount());
    }

    /** @test */
    public function unread_count_zero_if_thread_read()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->group()->create();
        Message::factory()->for($thread)->owner($this->doe)->count(3)->create();
        Participant::factory()->for($thread)->owner($this->tippin)->read()->create();

        $this->assertSame(0, $thread->unreadCount());
    }

    /** @test */
    public function it_gets_knock_lockout_cache_key()
    {
        $private = Thread::factory()->create();
        $group = Thread::factory()->group()->create();

        $private->getKnockCacheKey($this->tippin);
        $group->getKnockCacheKey();

        $this->assertSame("knock.knock.$group->id", $group->getKnockCacheKey());
        $this->assertSame("knock.knock.$private->id.", $private->getKnockCacheKey());
        $this->assertSame(
            "knock.knock.$private->id.{$this->tippin->getKey()}",
            $private->getKnockCacheKey($this->tippin)
        );
    }
}
