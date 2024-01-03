<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class ImageMessageTest extends HttpTestCase
{
    /** @test */
    public function user_can_view_image_messages()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        Message::factory()->for($thread)->owner($this->tippin)->image()->count(2)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.images.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_view_paginated_image_messages()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        Message::factory()->for($thread)->owner($this->tippin)->image()->count(2)->create();
        $image = Message::factory()->for($thread)->owner($this->tippin)->image()->create();
        Message::factory()->for($thread)->owner($this->tippin)->image()->count(2)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.images.page', [
            'thread' => $thread->id,
            'image' => $image->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_send_image_message()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $thread->id,
        ]), [
            'image' => UploadedFile::fake()->image('picture.jpg'),
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $thread->id,
                'temporary_id' => '123-456-789',
                'type' => 1,
                'type_verbose' => 'IMAGE_MESSAGE',
            ]);
    }

    /** @test */
    public function user_can_send_image_message_with_extra()
    {
        $this->logCurrentRequest('EXTRA');
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $thread->id,
        ]), [
            'image' => UploadedFile::fake()->image('picture.jpg'),
            'temporary_id' => '123-456-789',
            'extra' => ['test' => true],
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $thread->id,
                'extra' => [
                    'test' => true,
                ],
            ]);
    }

    /** @test */
    public function image_message_mime_types_can_be_overwritten()
    {
        Messenger::setMessageImageMimeTypes('cr2');
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $thread->id,
        ]), [
            'image' => UploadedFile::fake()->create('avatar.cr2', 500, 'image/x-canon-cr2'),
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function image_message_size_limit_can_be_overwritten()
    {
        Messenger::setMessageImageSizeLimit(20480);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $thread->id,
        ]), [
            'image' => UploadedFile::fake()->create('avatar.jpg', 18000, 'image/jpeg'),
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function user_forbidden_to_send_image_message_when_disabled_from_config()
    {
        $this->logCurrentRequest();
        Messenger::setMessageImageUpload(false);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $thread->id,
        ]), [
            'image' => UploadedFile::fake()->image('picture.jpg'),
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /**
     * @test
     *
     * @dataProvider imagePassesValidation
     *
     * @param  $imageValue
     */
    public function send_image_message_upload_passes_validation($imageValue)
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $thread->id,
        ]), [
            'image' => $imageValue,
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     *
     * @dataProvider imageFailedValidation
     *
     * @param  $imageValue
     */
    public function send_image_message_upload_fails_validation($imageValue)
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $thread->id,
        ]), [
            'image' => $imageValue,
            'temporary_id' => '123-456-789',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public static function imageFailedValidation(): array
    {
        return [
            'Image cannot be empty' => [''],
            'Image cannot be integer' => [5],
            'Image cannot be null' => [null],
            'Image cannot be an array' => [[1, 2]],
            'Image cannot be a movie' => [UploadedFile::fake()->create('movie.mov', 500, 'video/quicktime')],
            'Image must be 5120 kb or less' => [UploadedFile::fake()->create('image.jpg', 5121, 'image/jpeg')],
            'Image cannot be a pdf' => [UploadedFile::fake()->create('test.pdf', 500, 'application/pdf')],
            'Image cannot be text file' => [UploadedFile::fake()->create('test.txt', 500, 'text/plain')],
            'Image cannot be svg' => [UploadedFile::fake()->create('image.svg', 500, 'image/svg+xml')],
        ];
    }

    public static function imagePassesValidation(): array
    {
        return [
            'Image can be jpeg' => [UploadedFile::fake()->create('image.jpeg', 500, 'image/jpeg')],
            'Image can be png' => [UploadedFile::fake()->create('image.png', 500, 'image/png')],
            'Image can be bmp' => [UploadedFile::fake()->create('image.bmp', 500, 'image/bmp')],
            'Image can be gif' => [UploadedFile::fake()->create('image.gif', 500, 'image/gif')],
            'Image can be webp' => [UploadedFile::fake()->create('image.svg', 500, 'image/webp')],
            'Image can be 5120 kb max limit' => [UploadedFile::fake()->create('image.jpg', 5120, 'image/jpeg')],
        ];
    }
}
