<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ImageMessageTest extends FeatureTestCase
{
    private Thread $private;

    protected function setUp(): void
    {
        parent::setUp();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);
        Storage::fake(Messenger::getThreadStorage('disk'));
    }

    /** @test */
    public function user_can_send_image_message()
    {
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
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->private->id,
                'temporary_id' => '123-456-789',
                'type' => 1,
                'type_verbose' => 'IMAGE_MESSAGE',
                'owner' => [
                    'provider_id' => $this->tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ]);
    }

    /** @test */
    public function user_can_send_image_message_with_extra()
    {
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
            'extra' => ['test' => true],
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->private->id,
                'temporary_id' => '123-456-789',
                'type' => 1,
                'type_verbose' => 'IMAGE_MESSAGE',
                'extra' => [
                    'test' => true,
                ],
                'owner' => [
                    'provider_id' => $this->tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ]);
    }

    /** @test */
    public function image_message_mime_types_can_be_overwritten()
    {
        Messenger::setMessageImageMimeTypes('cr2');
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $this->private->id,
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
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $this->private->id,
        ]), [
            'image' => UploadedFile::fake()->create('avatar.jpg', 18000, 'image/jpeg'),
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function user_forbidden_to_send_image_message_when_disabled_from_config()
    {
        Messenger::setMessageImageUpload(false);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $this->private->id,
        ]), [
            'image' => UploadedFile::fake()->image('picture.jpg'),
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /**
     * @test
     * @dataProvider imagePassesValidation
     * @param $imageValue
     */
    public function send_image_message_upload_passes_validation($imageValue)
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $this->private->id,
        ]), [
            'image' => $imageValue,
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     * @dataProvider imageFailedValidation
     * @param $imageValue
     */
    public function send_image_message_upload_fails_validation($imageValue)
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $this->private->id,
        ]), [
            'image' => $imageValue,
            'temporary_id' => '123-456-789',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public function imageFailedValidation(): array
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
        ];
    }

    public function imagePassesValidation(): array
    {
        return [
            'Image can be jpeg' => [UploadedFile::fake()->create('image.jpeg', 500, 'image/jpeg')],
            'Image can be png' => [UploadedFile::fake()->create('image.png', 500, 'image/png')],
            'Image can be bmp' => [UploadedFile::fake()->create('image.bmp', 500, 'image/bmp')],
            'Image can be gif' => [UploadedFile::fake()->create('image.gif', 500, 'image/gif')],
            'Image can be svg' => [UploadedFile::fake()->create('image.svg', 500, 'image/svg+xml')],
            'Image can be webp' => [UploadedFile::fake()->create('image.svg', 500, 'image/webp')],
            'Image can be 5120 kb max limit' => [UploadedFile::fake()->create('image.jpg', 5120, 'image/jpeg')],
        ];
    }
}
