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

class ThreadTest extends FeatureTestCase
{
    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);
    }

    /** @test */
    public function threads_exists()
    {
        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseHas('threads', [
            'id' => $this->group->id,
        ]);
        $this->assertInstanceOf(Thread::class, $this->group);
        $this->assertSame(0, Thread::private()->count());
        $this->assertSame(1, Thread::group()->count());
    }

    /** @test */
    public function thread_attributes_casted()
    {
        $this->group->update([
            'deleted_at' => now(),
        ]);

        $this->assertInstanceOf(Carbon::class, $this->group->created_at);
        $this->assertInstanceOf(Carbon::class, $this->group->updated_at);
        $this->assertInstanceOf(Carbon::class, $this->group->deleted_at);
        $this->assertTrue($this->group->add_participants);
        $this->assertTrue($this->group->invitations);
        $this->assertTrue($this->group->calling);
        $this->assertTrue($this->group->messaging);
        $this->assertTrue($this->group->knocks);
        $this->assertSame(2, $this->group->type);
        $this->assertSame('First Test Group', $this->group->subject);
    }

    /** @test */
    public function thread_has_relations()
    {
        $this->assertInstanceOf(Collection::class, $this->group->participants);
        $this->assertInstanceOf(Collection::class, $this->group->messages);
        $this->assertInstanceOf(Collection::class, $this->group->calls);
        $this->assertInstanceOf(Collection::class, $this->group->invites);
    }

    /** @test */
    public function thread_has_active_call()
    {
        $this->createCall($this->group, $this->tippin);

        $this->assertInstanceOf(Call::class, $this->group->activeCall);
        $this->assertSame($this->group->id, $this->group->activeCall->thread_id);
        $this->assertTrue($this->group->hasActiveCall());
    }

    /** @test */
    public function thread_does_not_have_active_call()
    {
        $this->assertNull($this->group->activeCall);
        $this->assertFalse($this->group->hasActiveCall());
    }

    /** @test */
    public function thread_has_latest_message()
    {
        $this->createMessage($this->group, $this->tippin);

        $this->travel(5)->minutes();

        $latest = $this->createMessage($this->group, $this->tippin);

        $this->assertInstanceOf(Message::class, $this->group->recentMessage);
        $this->assertSame($this->group->id, $this->group->recentMessage->thread_id);
        $this->assertSame($latest->id, $this->group->recentMessage->id);
    }

    /** @test */
    public function thread_does_not_have_latest_message()
    {
        $this->assertNull($this->group->recentMessage);
    }

    /** @test */
    public function thread_types()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->assertTrue($private->isPrivate());
        $this->assertFalse($private->isGroup());
        $this->assertTrue($this->group->isGroup());
        $this->assertFalse($this->group->isPrivate());
        $this->assertSame('PRIVATE', $private->getTypeVerbose());
        $this->assertSame('GROUP', $this->group->getTypeVerbose());
    }

    /** @test */
    public function thread_storage()
    {
        $this->group->update([
            'image' => 'test.png',
        ]);

        $this->assertSame('messenger', $this->group->getStorageDisk());
        $this->assertSame("threads/{$this->group->id}", $this->group->getStorageDirectory());
        $this->assertSame("threads/{$this->group->id}/avatar/test.png", $this->group->getAvatarPath());
    }

    /** @test */
    public function thread_does_not_have_current_participant_when_provider_not_set()
    {
        $this->assertNull($this->group->currentParticipant());
        $this->assertFalse($this->group->hasCurrentProvider());
    }

    /** @test */
    public function thread_does_not_have_current_participant_when_not_in_thread()
    {
        Messenger::setProvider($this->companyDevelopers());

        $this->assertNull($this->group->currentParticipant());
        $this->assertFalse($this->group->hasCurrentProvider());
    }

    /** @test */
    public function thread_has_current_participant()
    {
        Messenger::setProvider($this->tippin);

        $this->assertInstanceOf(Participant::class, $this->group->currentParticipant());
        $this->assertTrue($this->group->hasCurrentProvider());
        $this->assertEquals($this->tippin->getKey(), $this->group->currentParticipant()->owner_id);
    }

    /** @test */
    public function thread_recipient_returns_ghost_participant_when_group_thread()
    {
        Messenger::setProvider($this->tippin);

        $this->assertInstanceOf(Participant::class, $this->group->recipient());
        $this->assertInstanceOf(GhostUser::class, $this->group->recipient()->owner);
        $this->assertSame($this->group->id, $this->group->recipient()->thread_id);
        $this->assertNull($this->group->recipient()->owner_id);
    }

    /** @test */
    public function thread_recipient_returns_ghost_participant_when_not_in_thread()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->companyDevelopers());

        $this->assertInstanceOf(Participant::class, $private->recipient());
        $this->assertInstanceOf(GhostUser::class, $private->recipient()->owner);
        $this->assertSame($private->id, $private->recipient()->thread_id);
        $this->assertNull($private->recipient()->owner_id);
    }

    /** @test */
    public function thread_recipient_returns_other_participant_in_private_thread()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->tippin);

        $this->assertInstanceOf(Participant::class, $private->recipient());
        $this->assertInstanceOf(MessengerProvider::class, $private->recipient()->owner);
        $this->assertSame($private->id, $private->recipient()->thread_id);
        $this->assertEquals($this->doe->getKey(), $private->recipient()->owner_id);
    }

    /** @test */
    public function thread_recipient_returns_ghost_participant_when_invalid_recipient()
    {
        $private = $this->createPrivateThread($this->tippin, $this->companyDevelopers());

        Messenger::setMessengerProviders(['user' => $this->getBaseProvidersConfig()['user']]);

        Messenger::setProvider($this->tippin);

        $this->assertInstanceOf(Participant::class, $private->recipient());
        $this->assertInstanceOf(GhostUser::class, $private->recipient()->owner);
        $this->assertSame($private->id, $private->recipient()->thread_id);
        $this->assertNull($private->recipient()->owner_id);
    }

    /** @test */
    public function thread_has_name_depending_on_type()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->tippin);

        $this->assertSame('First Test Group', $this->group->name());
        $this->assertSame('John Doe', $private->name());
    }

    /** @test */
    public function thread_has_avatar_depending_on_type()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->group->update([
            'image' => 'test.png',
        ]);

        Messenger::setProvider($this->tippin);

        $groupAvatar = [
            'sm' => "/messenger/threads/{$this->group->id}/avatar/sm/test.png",
            'md' => "/messenger/threads/{$this->group->id}/avatar/md/test.png",
            'lg' => "/messenger/threads/{$this->group->id}/avatar/lg/test.png",
        ];

        $groupAvatarApi = [
            'sm' => "/api/messenger/threads/{$this->group->id}/avatar/sm/test.png",
            'md' => "/api/messenger/threads/{$this->group->id}/avatar/md/test.png",
            'lg' => "/api/messenger/threads/{$this->group->id}/avatar/lg/test.png",
        ];

        $privateAvatar = [
            'sm' => "/images/user/{$this->doe->getKey()}/sm/default.png",
            'md' => "/images/user/{$this->doe->getKey()}/md/default.png",
            'lg' => "/images/user/{$this->doe->getKey()}/lg/default.png",
        ];

        $privateAvatarApi = [
            'sm' => "/api/messenger/images/user/{$this->doe->getKey()}/sm/default.png",
            'md' => "/api/messenger/images/user/{$this->doe->getKey()}/md/default.png",
            'lg' => "/api/messenger/images/user/{$this->doe->getKey()}/lg/default.png",
        ];

        $this->assertSame($groupAvatar, $this->group->threadAvatar());
        $this->assertSame($groupAvatarApi, $this->group->threadAvatar(true));
        $this->assertSame($privateAvatar, $private->threadAvatar());
        $this->assertSame($privateAvatarApi, $private->threadAvatar(true));
    }

    /** @test */
    public function is_thread_admin()
    {
        Messenger::setProvider($this->tippin);

        $this->assertTrue($this->group->isAdmin());
    }

    /** @test */
    public function is_not_thread_admin()
    {
        Messenger::setProvider($this->doe);

        $this->assertFalse($this->group->isAdmin());
    }

    /** @test */
    public function private_thread_has_no_admin()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($private->isAdmin());
    }

    /** @test */
    public function thread_is_not_locked()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($private->isLocked());
        $this->assertFalse($this->group->isLocked());
    }

    /** @test */
    public function thread_locked_when_not_participant()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->companyDevelopers());

        $this->assertTrue($private->isLocked());
        $this->assertTrue($this->group->isLocked());
    }

    /** @test */
    public function threads_locked_when_set_in_thread_settings()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->group->update([
            'lockout' => true,
        ]);

        $private->update([
            'lockout' => true,
        ]);

        Messenger::setProvider($this->tippin);

        $this->assertTrue($private->isLocked());
        $this->assertTrue($this->group->isLocked());
    }

    /** @test */
    public function private_thread_locked_when_recipient_not_found()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->doe->delete();

        Messenger::setProvider($this->tippin);

        $this->assertTrue($private->isLocked());
    }

    /** @test */
    public function thread_is_not_muted()
    {
        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->isMuted());
    }

    /** @test */
    public function thread_is_muted()
    {
        $this->group->participants()
            ->admins()
            ->first()
            ->update([
                'muted' => true,
            ]);

        Messenger::setProvider($this->tippin);

        $this->assertTrue($this->group->isMuted());
    }

    /** @test */
    public function group_thread_is_not_pending()
    {
        $this->group->participants()
            ->admins()
            ->first()
            ->update([
                'pending' => true,
            ]);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->isPending());
    }

    /** @test */
    public function private_thread_pending_and_awaiting_approval_when_provider_pending()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $private->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first()
            ->update([
                'pending' => true,
            ]);

        Messenger::setProvider($this->tippin);

        $this->assertTrue($private->isPending());
        $this->assertTrue($private->isAwaitingMyApproval());
    }

    /** @test */
    public function private_thread_pending_for_other_participant_not_awaiting_their_approval()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $private->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first()
            ->update([
                'pending' => true,
            ]);

        Messenger::setProvider($this->doe);

        $this->assertTrue($private->isPending());
        $this->assertFalse($private->isAwaitingMyApproval());
    }

    /** @test */
    public function can_message_threads()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->doe);

        $this->assertTrue($private->canMessage());
        $this->assertTrue($this->group->canMessage());
    }

    /** @test */
    public function cannot_message_when_thread_locked()
    {
        $this->group->update([
            'lockout' => true,
        ]);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->canMessage());
    }

    /** @test */
    public function cannot_message_when_disabled_in_thread_settings()
    {
        $this->group->update([
            'messaging' => false,
        ]);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->canMessage());
    }

    /** @test */
    public function cannot_message_when_awaiting_approval()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $private->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first()
            ->update([
                'pending' => true,
            ]);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($private->canMessage());
    }

    /** @test */
    public function can_message_when_disabled_on_participant_but_is_admin()
    {
        $this->group->participants()
            ->admins()
            ->first()
            ->update([
                'send_messages' => false,
            ]);

        Messenger::setProvider($this->tippin);

        $this->assertTrue($this->group->canMessage());
    }

    /** @test */
    public function cannot_message_when_disabled_on_participant_and_not_admin()
    {
        $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'send_messages' => false,
            ]);

        Messenger::setProvider($this->doe);

        $this->assertFalse($this->group->canMessage());
    }

    /** @test */
    public function can_add_participants()
    {
        Messenger::setProvider($this->tippin);

        $this->assertTrue($this->group->canAddParticipants());
    }

    /** @test */
    public function cannot_add_participants_when_thread_locked()
    {
        $this->group->update([
            'lockout' => true,
        ]);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->canAddParticipants());
    }

    /** @test */
    public function cannot_add_participants_when_disabled_on_thread_settings()
    {
        $this->group->update([
            'add_participants' => false,
        ]);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->canAddParticipants());
    }

    /** @test */
    public function can_add_participants_when_disabled_on_participant_but_is_admin()
    {
        $this->group->participants()
            ->admins()
            ->first()
            ->update([
                'add_participants' => false,
            ]);

        Messenger::setProvider($this->tippin);

        $this->assertTrue($this->group->canAddParticipants());
    }

    /** @test */
    public function non_admin_without_permission_cannot_add_participants()
    {
        Messenger::setProvider($this->doe);

        $this->assertFalse($this->group->canAddParticipants());
    }

    /** @test */
    public function non_admin_with_permission_can_add_participants()
    {
        $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'add_participants' => true,
            ]);

        Messenger::setProvider($this->doe);

        $this->assertTrue($this->group->canAddParticipants());
    }

    /** @test */
    public function cannot_add_participants_to_private_thread()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($private->canAddParticipants());
    }

    /** @test */
    public function cannot_invite_participants_to_private_thread()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($private->canInviteParticipants());
    }

    /** @test */
    public function admin_can_invite_participants()
    {
        Messenger::setProvider($this->tippin);

        $this->assertTrue($this->group->canInviteParticipants());
    }

    /** @test */
    public function non_admin_without_permission_cannot_invite_participants()
    {
        Messenger::setProvider($this->doe);

        $this->assertFalse($this->group->canInviteParticipants());
    }

    /** @test */
    public function non_admin_with_permission_can_invite_participants()
    {
        $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'manage_invites' => true,
            ]);

        Messenger::setProvider($this->doe);

        $this->assertTrue($this->group->canInviteParticipants());
    }

    /** @test */
    public function can_invite_participants_when_disabled_on_participant_but_is_admin()
    {
        $this->group->participants()
            ->admins()
            ->first()
            ->update([
                'manage_invites' => false,
            ]);

        Messenger::setProvider($this->tippin);

        $this->assertTrue($this->group->canInviteParticipants());
    }

    /** @test */
    public function cannot_invite_participants_when_disabled_in_thread_settings()
    {
        $this->group->update([
            'invitations' => false,
        ]);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->canInviteParticipants());
    }

    /** @test */
    public function cannot_invite_participants_when_thread_locked()
    {
        $this->group->update([
            'lockout' => true,
        ]);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->canInviteParticipants());
    }

    /** @test */
    public function cannot_invite_participants_when_disabled_in_config()
    {
        Messenger::setThreadInvites(false);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->canInviteParticipants());
    }

    /** @test */
    public function can_join_with_invite()
    {
        Messenger::setProvider($this->companyDevelopers());

        $this->assertTrue($this->group->canJoinWithInvite());
    }

    /** @test */
    public function cannot_join_with_invite_when_no_provider_set()
    {
        $this->assertFalse($this->group->canJoinWithInvite());
    }

    /** @test */
    public function cannot_join_with_invite_when_disabled_in_config()
    {
        Messenger::setThreadInvites(false);

        Messenger::setProvider($this->companyDevelopers());

        $this->assertFalse($this->group->canJoinWithInvite());
    }

    /** @test */
    public function cannot_join_with_invite_when_already_in_thread()
    {
        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->canJoinWithInvite());
    }

    /** @test */
    public function cannot_join_with_invite_when_thread_locked()
    {
        $this->group->update([
            'lockout' => true,
        ]);

        Messenger::setProvider($this->companyDevelopers());

        $this->assertFalse($this->group->canJoinWithInvite());
    }

    /** @test */
    public function cannot_join_with_invite_when_disabled_in_thread_settings()
    {
        $this->group->update([
            'invitations' => false,
        ]);

        Messenger::setProvider($this->companyDevelopers());

        $this->assertFalse($this->group->canJoinWithInvite());
    }

    /** @test */
    public function cannot_join_with_invite_when_in_private_thread()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->companyDevelopers());

        $this->assertFalse($private->canJoinWithInvite());
    }

    /** @test */
    public function can_start_call_in_threads()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->tippin);

        $this->assertTrue($private->canCall());
        $this->assertTrue($this->group->canCall());
    }

    /** @test */
    public function can_start_call_when_disabled_on_participant_but_is_admin()
    {
        $this->group->participants()
            ->admins()
            ->first()
            ->update([
                'start_calls' => false,
            ]);

        Messenger::setProvider($this->tippin);

        $this->assertTrue($this->group->canCall());
    }

    /** @test */
    public function can_start_call_when_disabled_on_participant_but_is_private_thread()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $private->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first()
            ->update([
                'start_calls' => false,
            ]);

        Messenger::setProvider($this->tippin);

        $this->assertTrue($private->canCall());
    }

    /** @test */
    public function cannot_start_call_when_private_thread_is_pending()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $private->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'pending' => true,
            ]);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($private->canCall());
    }

    /** @test */
    public function non_admin_without_permissions_cannot_start_call()
    {
        Messenger::setProvider($this->doe);

        $this->assertFalse($this->group->canCall());
    }

    /** @test */
    public function cannot_start_call_when_disabled_in_config()
    {
        Messenger::setCalling(false);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->canCall());
    }

    /** @test */
    public function cannot_start_call_when_temporarily_disabled()
    {
        Messenger::disableCallsTemporarily(1);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->canCall());
    }

    /** @test */
    public function cannot_start_call_when_thread_locked()
    {
        $this->group->update([
            'lockout' => true,
        ]);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->canCall());
    }

    /** @test */
    public function cannot_start_call_when_disabled_in_thread_settings()
    {
        $this->group->update([
            'calling' => false,
        ]);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->canCall());
    }

    /** @test */
    public function can_knock_in_threads()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->tippin);

        $this->assertTrue($private->canKnock());
        $this->assertTrue($this->group->canKnock());
    }

    /** @test */
    public function can_knock_when_disabled_on_participant_but_is_admin()
    {
        $this->group->participants()
            ->admins()
            ->first()
            ->update([
                'send_knocks' => false,
            ]);

        Messenger::setProvider($this->tippin);

        $this->assertTrue($this->group->canKnock());
    }

    /** @test */
    public function can_knock_when_disabled_on_participant_but_is_private_thread()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $private->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first()
            ->update([
                'send_knocks' => false,
            ]);

        Messenger::setProvider($this->tippin);

        $this->assertTrue($private->canKnock());
    }

    /** @test */
    public function cannot_knock_when_private_thread_is_pending()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $private->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'pending' => true,
            ]);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($private->canKnock());
    }

    /** @test */
    public function non_admin_without_permissions_cannot_knock()
    {
        Messenger::setProvider($this->doe);

        $this->assertFalse($this->group->canKnock());
    }

    /** @test */
    public function cannot_knock_when_disabled_in_config()
    {
        Messenger::setKnockKnock(false);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->canKnock());
    }

    /** @test */
    public function cannot_knock_when_thread_locked()
    {
        $this->group->update([
            'lockout' => true,
        ]);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->canKnock());
    }

    /** @test */
    public function cannot_knock_when_disabled_in_thread_settings()
    {
        $this->group->update([
            'knocks' => false,
        ]);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->canKnock());
    }

    /** @test */
    public function thread_is_unread_when_last_read_null()
    {
        Messenger::setProvider($this->tippin);

        $this->assertTrue($this->group->isUnread());
    }

    /** @test */
    public function thread_is_unread_when_last_read_less_than_thread_last_updated()
    {
        $this->group->participants()
            ->admins()
            ->first()
            ->update([
                'last_read' => now(),
            ]);

        $this->group->update([
            'updated_at' => now()->addHour(),
        ]);

        Messenger::setProvider($this->tippin);

        $this->assertTrue($this->group->isUnread());
    }

    /** @test */
    public function thread_not_unread_when_last_read_greater_than_thread_last_updated()
    {
        $this->group->participants()
            ->admins()
            ->first()
            ->update([
                'last_read' => now()->addHour(),
            ]);

        Messenger::setProvider($this->tippin);

        $this->assertFalse($this->group->isUnread());
    }

    /** @test */
    public function thread_not_unread_when_provider_not_set()
    {
        $this->assertFalse($this->group->isUnread());
    }

    /** @test */
    public function thread_not_unread_when_participant_not_in_thread()
    {
        Messenger::setProvider($this->companyDevelopers());

        $this->assertFalse($this->group->isUnread());
    }

    /** @test */
    public function thread_unread_count_matches_message_count_when_last_read_null()
    {
        $this->createMessage($this->group, $this->tippin);

        $this->createMessage($this->group, $this->tippin);

        $this->createMessage($this->group, $this->tippin);

        Messenger::setProvider($this->tippin);

        $this->assertSame(3, $this->group->unreadCount());
    }

    /** @test */
    public function thread_unread_count_matches_message_count_created_after_last_read()
    {
        $this->createMessage($this->group, $this->tippin);

        $this->createMessage($this->group, $this->tippin);

        $this->travel(1)->minutes();

        $this->group->participants()
            ->admins()
            ->first()
            ->update([
                'last_read' => now(),
            ]);

        $this->travel(1)->minutes();

        $this->createMessage($this->group, $this->tippin);

        $this->group->update([
            'updated_at' => now(),
        ]);

        Messenger::setProvider($this->tippin);

        $this->assertSame(1, $this->group->unreadCount());
    }

    /** @test */
    public function thread_unread_count_zero_when_no_messages()
    {
        Messenger::setProvider($this->tippin);

        $this->assertSame(0, $this->group->unreadCount());
    }

    /** @test */
    public function thread_unread_count_zero_when_thread_read()
    {
        $this->createMessage($this->group, $this->tippin);

        $this->group->participants()
            ->admins()
            ->first()
            ->update([
                'last_read' => now()->addHour(),
            ]);

        Messenger::setProvider($this->tippin);

        $this->assertSame(0, $this->group->unreadCount());
    }
}
