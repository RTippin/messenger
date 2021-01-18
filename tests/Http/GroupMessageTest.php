<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\MessageArchivedBroadcast;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\MessageArchivedEvent;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class GroupMessageTest extends FeatureTestCase
{
    private Thread $group;

    private Message $message;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);

        $this->message = $this->createMessage($this->group, $this->tippin);
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
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.messages.index', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function participant_can_view_message()
    {
        $this->actingAs($this->doe);

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
        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->actingAs($this->tippin);

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
                    'provider_id' => $this->tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ]);
    }

    /** @test */
    public function participant_can_send_message()
    {
        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->actingAs($this->doe);

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
        $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'send_messages' => false,
            ]);

        $this->actingAs($this->doe);

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
        $this->expectsEvents([
            MessageArchivedBroadcast::class,
            MessageArchivedEvent::class,
        ]);

        $message = $this->createMessage($this->group, $this->doe);

        $this->actingAs($this->doe);

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

        $message = $this->createMessage($this->group, $this->doe);

        $this->actingAs($this->tippin);

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

        $this->actingAs($this->tippin);

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

        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->group->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }
}
