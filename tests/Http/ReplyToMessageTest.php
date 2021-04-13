<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ReplyToMessageTest extends FeatureTestCase
{
    private Thread $private;
    private Message $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);
        $this->message = $this->createMessage($this->private, $this->doe);
    }

    /** @test */
    public function user_can_view_message_with_reply()
    {
        $replying = Message::factory()
            ->for($this->private)
            ->owner($this->tippin)
            ->create([
                'body' => 'Reply',
                'reply_to_id' => $this->message->id
            ]);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.show', [
            'thread' => $this->private->id,
            'message' => $replying->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->private->id,
                'type' => 0,
                'type_verbose' => 'MESSAGE',
                'body' => 'Reply',
                'reply_to_id' => $this->message->id,
                'reply_to' => [
                    'id' => $this->message->id,
                    'body' => 'First Test Message',
                    'owner' => [
                        'provider_id' => $this->doe->getKey(),
                        'provider_alias' => 'user',
                        'name' => 'John Doe',
                    ],
                ],
                'owner' => [
                    'provider_id' => $this->tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ]);
    }

    /** @test */
    public function reply_to_resource_omitted_when_reply_not_found()
    {
        $replying = Message::factory()
            ->for($this->private)
            ->owner($this->tippin)
            ->create([
                'body' => 'Reply',
                'reply_to_id' => '404'
            ]);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.show', [
            'thread' => $this->private->id,
            'message' => $replying->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->private->id,
                'type' => 0,
                'type_verbose' => 'MESSAGE',
                'body' => 'Reply',
                'reply_to_id' => '404',
                'reply_to' => null,
                'owner' => [
                    'provider_id' => $this->tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ]);
    }

    /** @test */
    public function user_can_reply_to_message()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->private->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
            'reply_to_id' => $this->message->id,
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->private->id,
                'temporary_id' => '123-456-789',
                'type' => 0,
                'type_verbose' => 'MESSAGE',
                'body' => 'Hello!',
                'reply_to_id' => $this->message->id,
                'reply_to' => [
                    'id' => $this->message->id,
                    'body' => 'First Test Message',
                    'owner' => [
                        'provider_id' => $this->doe->getKey(),
                        'provider_alias' => 'user',
                        'name' => 'John Doe',
                    ],
                ],
                'owner' => [
                    'provider_id' => $this->tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ]);
    }

    /** @test */
    public function user_can_reply_with_audio_message()
    {
        Storage::fake(Messenger::getThreadStorage('disk'));
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.audio.store', [
            'thread' => $this->private->id,
        ]), [
            'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
            'temporary_id' => '123-456-789',
            'reply_to_id' => $this->message->id,
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->private->id,
                'temporary_id' => '123-456-789',
                'type' => 3,
                'type_verbose' => 'AUDIO_MESSAGE',
                'reply_to_id' => $this->message->id,
                'reply_to' => [
                    'id' => $this->message->id,
                    'body' => 'First Test Message',
                    'owner' => [
                        'provider_id' => $this->doe->getKey(),
                        'provider_alias' => 'user',
                        'name' => 'John Doe',
                    ],
                ],
                'owner' => [
                    'provider_id' => $this->tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ]);
    }

    /** @test */
    public function user_can_reply_with_document_message()
    {
        Storage::fake(Messenger::getThreadStorage('disk'));
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.documents.store', [
            'thread' => $this->private->id,
        ]), [
            'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
            'temporary_id' => '123-456-789',
            'reply_to_id' => $this->message->id,
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->private->id,
                'temporary_id' => '123-456-789',
                'type' => 2,
                'type_verbose' => 'DOCUMENT_MESSAGE',
                'reply_to' => [
                    'id' => $this->message->id,
                    'body' => 'First Test Message',
                    'owner' => [
                        'provider_id' => $this->doe->getKey(),
                        'provider_alias' => 'user',
                        'name' => 'John Doe',
                    ],
                ],
                'owner' => [
                    'provider_id' => $this->tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ]);
    }

    /** @test */
    public function user_can_reply_with_image_message()
    {
        Storage::fake(Messenger::getThreadStorage('disk'));
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $this->private->id,
        ]), [
            'image' => UploadedFile::fake()->image('picture.jpg'),
            'temporary_id' => '123-456-789',
            'reply_to_id' => $this->message->id,
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->private->id,
                'temporary_id' => '123-456-789',
                'type' => 1,
                'type_verbose' => 'IMAGE_MESSAGE',
                'reply_to' => [
                    'id' => $this->message->id,
                    'body' => 'First Test Message',
                    'owner' => [
                        'provider_id' => $this->doe->getKey(),
                        'provider_alias' => 'user',
                        'name' => 'John Doe',
                    ],
                ],
                'owner' => [
                    'provider_id' => $this->tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ]);
    }

    /** @test */
    public function message_reply_ignored_if_message_not_found()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->private->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
            'reply_to_id' => '404',
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->private->id,
                'temporary_id' => '123-456-789',
                'type' => 0,
                'type_verbose' => 'MESSAGE',
                'body' => 'Hello!',
                'owner' => [
                    'provider_id' => $this->tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ])
            ->assertJsonMissing([
                'reply_to_id' => $this->message->id,
            ]);
    }
}
