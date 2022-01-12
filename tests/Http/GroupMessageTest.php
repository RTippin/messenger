<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class GroupMessageTest extends HttpTestCase
{
    /** @test */
    public function non_participant_is_forbidden()
    {
        $thread = Thread::factory()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.index', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_can_view_messages_index()
    {
        $thread = $this->createGroupThread($this->tippin);
        Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function participant_can_view_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.show', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $message->id,
            ]);
    }

    /** @test */
    public function admin_can_send_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $thread->id,
                'temporary_id' => '123-456-789',
                'type' => 0,
                'type_verbose' => 'MESSAGE',
                'body' => 'Hello!',
            ]);
    }

    /** @test */
    public function participant_can_send_message()
    {
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function forbidden_to_send_message_when_thread_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_send_message()
    {
        $thread = Thread::factory()->group()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function participant_forbidden_to_send_message_without_proper_permission()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create(['send_messages' => false]);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_archive_message_when_thread_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.messages.destroy', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_can_archive_own_message()
    {
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $message = Message::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.messages.destroy', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function participant_forbidden_to_archive_non_owned_message()
    {
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.messages.destroy', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_archive_another_participants_message()
    {
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $message = Message::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.messages.destroy', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function admin_forbidden_to_send_message_when_disabled_in_group_settings()
    {
        $thread = Thread::factory()->group()->create(['messaging' => false]);
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function participant_forbidden_to_send_message_when_disabled_in_group_settings()
    {
        $thread = Thread::factory()->group()->create(['messaging' => false]);
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }
}
