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

        $this->private = $this->createPrivateThread(
            $this->userTippin(),
            $this->userDoe()
        );
    }

    /** @test */
    public function user_can_send_image_message()
    {
        Storage::fake($this->private->getStorageDisk());

        $tippin = $this->userTippin();

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->actingAs($tippin);

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
                    'provider_id' => $tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ]);

        Storage::disk($this->private->getStorageDisk())
            ->assertExists($this->private->messages()->image()->first()->getImagePath());
    }

    /** @test */
    public function user_forbidden_to_send_image_message_when_disabled_from_config()
    {
        Messenger::setMessageImageUpload(false);

        $tippin = $this->userTippin();

        $this->actingAs($tippin);

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
     * @dataProvider imageValidation
     * @param $imageValue
     */
    public function send_image_message_validates_image_file($imageValue)
    {
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $this->private->id,
        ]), [
            'image' => $imageValue,
            'temporary_id' => '123-456-789',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public function imageValidation(): array
    {
        return [
            'Image cannot be empty' => [''],
            'Image cannot be integer' => [5],
            'Image cannot be null' => [null],
            'Image cannot be an array' => [[1, 2]],
            'Image cannot be a movie' => [UploadedFile::fake()->create('movie.mov', 500, 'video/quicktime')],
            'Image must be under 5mb' => [UploadedFile::fake()->create('image.jpg', 6000, 'image/jpeg')],
            'Image cannot be a pdf' => [UploadedFile::fake()->create('test.pdf', 500, 'application/pdf')],
        ];
    }
}
