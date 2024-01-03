<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class AudioMessageTest extends HttpTestCase
{
    /** @test */
    public function user_can_view_audio_messages()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        Message::factory()->for($thread)->owner($this->tippin)->audio()->count(2)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.audio.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_view_paginated_audio_messages()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        Message::factory()->for($thread)->owner($this->tippin)->audio()->count(2)->create();
        $audio = Message::factory()->for($thread)->owner($this->tippin)->audio()->create();
        Message::factory()->for($thread)->owner($this->tippin)->audio()->count(2)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.audio.page', [
            'thread' => $thread->id,
            'audio' => $audio->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_send_audio_message()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.audio.store', [
            'thread' => $thread->id,
        ]), [
            'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $thread->id,
                'temporary_id' => '123-456-789',
                'type' => 3,
                'type_verbose' => 'AUDIO_MESSAGE',
            ]);
    }

    /** @test */
    public function user_can_send_audio_message_with_extra()
    {
        $this->logCurrentRequest('EXTRA');
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.audio.store', [
            'thread' => $thread->id,
        ]), [
            'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
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
    public function audio_message_mime_types_can_be_overwritten()
    {
        Messenger::setMessageAudioMimeTypes('3gpp');
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.audio.store', [
            'thread' => $thread->id,
        ]), [
            'audio' => UploadedFile::fake()->create('test.3gp', 500, 'audio/3gpp'),
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function audio_message_size_limit_can_be_overwritten()
    {
        Messenger::setMessageAudioSizeLimit(20480);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.audio.store', [
            'thread' => $thread->id,
        ]), [
            'audio' => UploadedFile::fake()->create('test.mp3', 18000, 'audio/mpeg'),
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function user_forbidden_to_send_audio_message_when_disabled_from_config()
    {
        $this->logCurrentRequest();
        Messenger::setMessageAudioUpload(false);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.audio.store', [
            'thread' => $thread->id,
        ]), [
            'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /**
     * @test
     *
     * @dataProvider audioPassesValidation
     *
     * @param  $audioValue
     */
    public function send_audio_message_passes_audio_validation($audioValue)
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.audio.store', [
            'thread' => $thread->id,
        ]), [
            'audio' => $audioValue,
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     *
     * @dataProvider audioFailsValidation
     *
     * @param  $audioValue
     */
    public function send_audio_message_fails_audio_validation($audioValue)
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.audio.store', [
            'thread' => $thread->id,
        ]), [
            'audio' => $audioValue,
            'temporary_id' => '123-456-789',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('audio');
    }

    public static function audioFailsValidation(): array
    {
        return [
            'Audio cannot be empty' => [''],
            'Audio cannot be integer' => [5],
            'Audio cannot be null' => [null],
            'Audio cannot be an array' => [[1, 2]],
            'Audio cannot be a movie' => [UploadedFile::fake()->create('movie.mov', 500, 'video/quicktime')],
            'Audio cannot be a mp4' => [UploadedFile::fake()->create('movie.mp4', 500, 'video/mp4')],
            'Audio cannot be an image' => [UploadedFile::fake()->image('image.jpg')],
            'Audio must be 10240 kb or less' => [UploadedFile::fake()->create('test.mp3', 10241, 'audio/mpeg')],
        ];
    }

    public static function audioPassesValidation(): array
    {
        return [
            'Audio can be 10240 kb max limit' => [UploadedFile::fake()->create('test.mp3', 10240, 'audio/mpeg')],
            'Audio can be aac' => [UploadedFile::fake()->create('test.aac', 500, 'audio/aac')],
            'Audio can be mp3' => [UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg')],
            'Audio can be oga' => [UploadedFile::fake()->create('test.oga', 500, 'audio/ogg')],
            'Audio can be wav' => [UploadedFile::fake()->create('test.wav', 500, 'audio/wav')],
            'Audio can be weba' => [UploadedFile::fake()->create('test.weba', 500, 'audio/webm')],
            'Audio can be webm' => [UploadedFile::fake()->create('test.webm', 500, 'audio/webm')],
        ];
    }
}
