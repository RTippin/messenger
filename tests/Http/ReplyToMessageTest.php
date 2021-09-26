<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Tests\HttpTestCase;

class ReplyToMessageTest extends HttpTestCase
{
    /** @test */
    public function user_can_view_message_with_reply()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $replying = Message::factory()->for($thread)->owner($this->tippin)->reply($message->id)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.show', [
            'thread' => $thread->id,
            'message' => $replying->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $thread->id,
                'reply_to_id' => $message->id,
                'reply_to' => [
                    'id' => $message->id,
                ],
            ]);
    }

    /** @test */
    public function reply_to_message_omitted_when_reply_not_found()
    {
        $thread = $this->createGroupThread($this->tippin);
        $replying = Message::factory()->for($thread)->owner($this->tippin)->reply('404')->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.show', [
            'thread' => $thread->id,
            'message' => $replying->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $thread->id,
                'reply_to_id' => '404',
                'reply_to' => null,
            ]);
    }

    /** @test */
    public function user_can_reply_to_message()
    {
        $this->logCurrentRequest('REPLY');
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
            'reply_to_id' => $message->id,
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $thread->id,
                'type' => 0,
                'type_verbose' => 'MESSAGE',
                'body' => 'Hello!',
                'reply_to_id' => $message->id,
                'reply_to' => [
                    'id' => $message->id,
                ],
            ]);
    }

    /** @test */
    public function user_can_reply_with_audio_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.audio.store', [
            'thread' => $thread->id,
        ]), [
            'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
            'temporary_id' => '123-456-789',
            'reply_to_id' => $message->id,
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $thread->id,
                'type' => 3,
                'type_verbose' => 'AUDIO_MESSAGE',
                'reply_to_id' => $message->id,
                'reply_to' => [
                    'id' => $message->id,
                ],
            ]);
    }

    /** @test */
    public function user_can_reply_with_document_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.documents.store', [
            'thread' => $thread->id,
        ]), [
            'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
            'temporary_id' => '123-456-789',
            'reply_to_id' => $message->id,
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $thread->id,
                'type' => 2,
                'type_verbose' => 'DOCUMENT_MESSAGE',
                'reply_to_id' => $message->id,
                'reply_to' => [
                    'id' => $message->id,
                ],
            ]);
    }

    /** @test */
    public function user_can_reply_with_image_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $thread->id,
        ]), [
            'image' => UploadedFile::fake()->image('picture.jpg'),
            'temporary_id' => '123-456-789',
            'reply_to_id' => $message->id,
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $thread->id,
                'type' => 1,
                'type_verbose' => 'IMAGE_MESSAGE',
                'reply_to_id' => $message->id,
                'reply_to' => [
                    'id' => $message->id,
                ],
            ]);
    }

    /** @test */
    public function message_reply_ignored_if_message_not_found()
    {
        $uuid = Str::uuid()->toString();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
            'reply_to_id' => $uuid,
        ])
            ->assertSuccessful()
            ->assertJsonMissing([
                'reply_to_id' => $uuid,
            ]);
    }

    /** @test */
    public function message_reply_must_be_uuid()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
            'reply_to_id' => '1234',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reply_to_id');
    }
}
