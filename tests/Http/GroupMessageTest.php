<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\MessageArchivedBroadcast;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\MessageArchivedEvent;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class GroupMessageTest extends FeatureTestCase
{
    private Thread $group;

    private Message $message;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $this->group = $this->createGroupThread(
            $tippin,
            $this->userDoe(),
            $this->companyDevelopers()
        );

        $this->message = $this->createMessage(
            $this->group,
            $tippin
        );
    }

    /** @test */
    public function non_participant_is_forbidden()
    {
        $this->actingAs($this->createJaneSmith());

        $this->getJson(route('api.messenger.threads.messages.index', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_can_view_messages_index()
    {
        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.messages.index', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function participant_can_view_message()
    {
        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.messages.show', [
            'thread' => $this->group->id,
            'message' => $this->message->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->message->id,
                'body' => 'First Test Message',
            ]);
    }

    /** @test */
    public function admin_can_send_message()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $developers = $this->companyDevelopers();

        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->group->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->group->id,
                'temporary_id' => '123-456-789',
                'type' => 0,
                'type_verbose' => 'MESSAGE',
                'body' => 'Hello!',
                'owner' => [
                    'provider_id' => $tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ]);

        Event::assertDispatched(function (NewMessageBroadcast $event) use ($doe, $tippin, $developers) {
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-user.'.$tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-company.'.$developers->getKey(), $event->broadcastOn());
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);
            $this->assertSame('123-456-789', $event->broadcastWith()['temporary_id']);

            return true;
        });

        Event::assertDispatched(function (NewMessageEvent $event) {
            return $this->group->id === $event->message->thread_id;
        });
    }

    /** @test */
    public function participant_can_send_message()
    {
        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->actingAs($this->companyDevelopers());

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->group->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function non_participant_forbidden_to_send_message()
    {
        $this->actingAs($this->createJaneSmith());

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->group->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function participant_forbidden_to_send_message_without_proper_permission()
    {
        $doe = $this->userDoe();

        $this->group->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first()
            ->update([
                'send_messages' => false,
            ]);

        $this->actingAs($doe);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->group->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function participant_can_archive_own_message()
    {
        $doe = $this->userDoe();

        $this->expectsEvents([
            MessageArchivedBroadcast::class,
            MessageArchivedEvent::class,
        ]);

        $message = $this->createMessage($this->group, $doe);

        $this->actingAs($doe);

        $this->deleteJson(route('api.messenger.threads.messages.destroy', [
            'thread' => $this->group->id,
            'message' => $message->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_archive_another_participants_message()
    {
        $this->expectsEvents([
            MessageArchivedBroadcast::class,
            MessageArchivedEvent::class,
        ]);

        $message = $this->createMessage(
            $this->group,
            $this->userDoe()
        );

        $this->actingAs($this->userTippin());

        $this->deleteJson(route('api.messenger.threads.messages.destroy', [
            'thread' => $this->group->id,
            'message' => $message->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_forbidden_to_send_message_when_disabled_in_group_settings()
    {
        $this->group->update([
            'messaging' => false,
        ]);

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->group->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function participant_forbidden_to_send_message_when_disabled_in_group_settings()
    {
        $this->group->update([
            'messaging' => false,
        ]);

        $this->actingAs($this->userDoe());

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->group->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }
}
