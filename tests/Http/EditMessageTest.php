<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageEdit;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class EditMessageTest extends HttpTestCase
{
    /** @test */
    public function non_participant_is_forbidden()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.messages.history', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_view_message_edits_if_message_has_none()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.messages.history', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_view_message_edits_when_disabled_in_config()
    {
        Messenger::setMessageEditsView(false);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->edited()->create();
        MessageEdit::factory()->for($message)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.history', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_can_view_message_edits()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->edited()->create();
        MessageEdit::factory()->for($message)->count(2)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.history', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2);
    }

    /** @test */
    public function owner_can_edit_message()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->edited()->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.messages.update', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]), [
            'message' => 'Edited Message',
        ])
            ->assertSuccessful()
            ->assertJson([
                'id' => $message->id,
                'body' => 'Edited Message',
                'edited' => true,
            ]);
    }

    /** @test */
    public function non_owner_forbidden_to_update_message()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->putJson(route('api.messenger.threads.messages.update', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]), [
            'message' => 'Edited Message',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_update_message_when_disabled_in_config()
    {
        Messenger::setMessageEdits(false);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.messages.update', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]), [
            'message' => 'Edited Message',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_update_image_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->image()->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.messages.update', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]), [
            'message' => 'Edited Message',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_update_document_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->document()->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.messages.update', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]), [
            'message' => 'Edited Message',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_update_system_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->system()->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.messages.update', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]), [
            'message' => 'Edited Message',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_update_message_when_thread_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.messages.update', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]), [
            'message' => 'Edited Message',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function edit_message_cannot_be_more_than_5k_characters()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->edited()->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.messages.update', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]), [
            'message' => Str::random(5001),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    /**
     * @test
     * @dataProvider editFailsValidation
     * @param $value
     */
    public function edit_message_fails_validation($value)
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->edited()->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.messages.update', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]), [
            'message' => $value,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    public function editFailsValidation(): array
    {
        return [
            'Edit cannot be empty' => [''],
            'Edit cannot be integer' => [5],
            'Edit cannot be null' => [null],
            'Edit cannot be an array' => [[1, 2]],
            'Edit cannot be an image' => [UploadedFile::fake()->image('picture.png')],
        ];
    }
}
