<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\MessageEditedBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\MessageEditedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class EditMessageTest extends FeatureTestCase
{
    private Thread $private;

    private Message $message;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->message = $this->createMessage($this->private, $this->tippin);
    }

    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.threads.messages.history', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertUnauthorized();
    }

    /** @test */
    public function non_participant_is_forbidden()
    {
        $this->actingAs($this->companyDevelopers());

        $this->getJson(route('api.messenger.threads.messages.history', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_view_message_edits_if_message_has_none()
    {
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.messages.history', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_view_message_edits_when_disabled_in_config()
    {
        Messenger::setMessageEditsView(false);

        $this->travel(10)->minutes();

        $this->message->edits()->create([
            'body' => 'First Edit',
            'edited_at' => now(),
        ]);

        $this->message->touch();

        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.history', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function recipient_can_view_message_edits()
    {
        $this->travel(10)->minutes();

        $this->message->edits()->create([
            'body' => 'First Edit',
            'edited_at' => now(),
        ]);

        $this->message->touch();

        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.messages.history', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'body' => 'First Edit',
                ],
            ]);
    }

    /** @test */
    public function can_view_multiple_message_edits()
    {
        $this->travel(10)->minutes();

        $this->message->edits()->create([
            'body' => 'First Edit',
            'edited_at' => now(),
        ]);

        $this->message->edits()->create([
            'body' => 'Second Edit',
            'edited_at' => now(),
        ]);

        $this->message->touch();

        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.history', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2);
    }

    /** @test */
    public function owner_can_edit_message()
    {
        $this->expectsEvents([
            MessageEditedBroadcast::class,
            MessageEditedEvent::class,
        ]);

        $this->travel(5)->minutes();

        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.messages.update', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]), [
            'message' => 'Edited Message',
        ])
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->message->id,
                'body' => 'Edited Message',
                'edited' => true,
            ]);
    }

    /** @test */
    public function non_owner_forbidden_to_update_message()
    {
        $this->actingAs($this->doe);

        $this->putJson(route('api.messenger.threads.messages.update', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]), [
            'message' => 'Edited Message',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_update_message_when_disabled_in_config()
    {
        Messenger::setMessageEdits(false);

        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.messages.update', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]), [
            'message' => 'Edited Message',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_update_image_message()
    {
        $this->message->update([
            'type' => 1,
        ]);

        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.messages.update', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]), [
            'message' => 'Edited Message',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_update_document_message()
    {
        $this->message->update([
            'type' => 2,
        ]);

        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.messages.update', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]), [
            'message' => 'Edited Message',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_update_system_message()
    {
        $this->message->update([
            'type' => 99,
        ]);

        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.messages.update', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]), [
            'message' => 'Edited Message',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_update_message_when_thread_locked()
    {
        $this->private->update([
            'lockout' => true,
        ]);

        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.messages.update', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]), [
            'message' => 'Edited Message',
        ])
            ->assertForbidden();
    }
}
