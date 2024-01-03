<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class VideoMessageTest extends HttpTestCase
{
    /** @test */
    public function user_can_view_video_messages()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        Message::factory()->for($thread)->owner($this->tippin)->video()->count(2)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.videos.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_view_paginated_video_messages()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        Message::factory()->for($thread)->owner($this->tippin)->video()->count(2)->create();
        $video = Message::factory()->for($thread)->owner($this->tippin)->video()->create();
        Message::factory()->for($thread)->owner($this->tippin)->video()->count(2)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.videos.page', [
            'thread' => $thread->id,
            'video' => $video->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_send_video_message()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.videos.store', [
            'thread' => $thread->id,
        ]), [
            'video' => UploadedFile::fake()->create('test.mov', 500, 'video/quicktime'),
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $thread->id,
                'temporary_id' => '123-456-789',
                'type' => Message::VIDEO_MESSAGE,
                'type_verbose' => 'VIDEO_MESSAGE',
            ]);
    }

    /** @test */
    public function user_can_send_video_message_with_extra()
    {
        $this->logCurrentRequest('EXTRA');
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.videos.store', [
            'thread' => $thread->id,
        ]), [
            'video' => UploadedFile::fake()->create('test.mov', 500, 'video/quicktime'),
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
    public function video_message_mime_types_can_be_overwritten()
    {
        Messenger::setMessageVideoMimeTypes('ts');
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.videos.store', [
            'thread' => $thread->id,
        ]), [
            'video' => UploadedFile::fake()->create('video.ts', 500, 'video/mp2t'),
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function video_message_size_limit_can_be_overwritten()
    {
        Messenger::setMessageVideoSizeLimit(20480);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.videos.store', [
            'thread' => $thread->id,
        ]), [
            'video' => UploadedFile::fake()->create('test.mov', 20000, 'video/quicktime'),
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function user_forbidden_to_send_video_message_when_disabled_from_config()
    {
        $this->logCurrentRequest();
        Messenger::setMessageVideoUpload(false);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.videos.store', [
            'thread' => $thread->id,
        ]), [
            'video' => UploadedFile::fake()->create('test.mov', 500, 'video/quicktime'),
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /**
     * @test
     *
     * @dataProvider videoPassesValidation
     *
     * @param  $videoValue
     */
    public function send_video_message_passes_video_validation($videoValue)
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.videos.store', [
            'thread' => $thread->id,
        ]), [
            'video' => $videoValue,
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     *
     * @dataProvider videoFailsValidation
     *
     * @param  $videoValue
     */
    public function send_video_message_fails_video_validation($videoValue)
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.videos.store', [
            'thread' => $thread->id,
        ]), [
            'video' => $videoValue,
            'temporary_id' => '123-456-789',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('video');
    }

    public static function videoFailsValidation(): array
    {
        return [
            'Video cannot be empty' => [''],
            'Video cannot be integer' => [5],
            'Video cannot be null' => [null],
            'Video cannot be an array' => [[1, 2]],
            'Video cannot be a pdf' => [UploadedFile::fake()->create('test.pdf', 500, 'application/pdf')],
            'Video cannot be a mp3' => [UploadedFile::fake()->create('song.mp3', 500, 'audio/mpeg')],
            'Video cannot be a wav song' => [UploadedFile::fake()->create('song.wav', 500, 'audio/wav')],
            'Video must be 15360 kb or less' => [UploadedFile::fake()->create('video.mov', 15361, 'video/quicktime')],
            'Video cannot be an image' => [UploadedFile::fake()->image('picture.png')],
        ];
    }

    public static function videoPassesValidation(): array
    {
        return [
            'Video can be avi' => [UploadedFile::fake()->create('video.avi', 500, 'video/x-msvideo')],
            'Video can be mp4' => [UploadedFile::fake()->create('video.mp4', 500, 'video/mp4')],
            'Video can be ogv' => [UploadedFile::fake()->create('video.ogv', 500, 'video/ogg')],
            'Video can be webm' => [UploadedFile::fake()->create('video.webm', 500, 'video/webm')],
            'Video can be 3gp' => [UploadedFile::fake()->create('video.3gp', 500, 'video/3gpp')],
            'Video can be 3g2' => [UploadedFile::fake()->create('video.3g2', 500, 'video/3gpp2')],
            'Video can be wmv' => [UploadedFile::fake()->create('video.wmv', 500, 'video/x-ms-wmv')],
            'Video can be mov' => [UploadedFile::fake()->create('video.mov', 500, 'video/quicktime')],
            'Video can be 15360 kb max limit' => [UploadedFile::fake()->create('video.mov', 15360, 'video/quicktime')],
        ];
    }
}
