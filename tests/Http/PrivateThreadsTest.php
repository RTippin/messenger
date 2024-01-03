<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Tests\Fixtures\UserModel;
use RTippin\Messenger\Tests\HttpTestCase;

class PrivateThreadsTest extends HttpTestCase
{
    /** @test */
    public function user_has_private_threads()
    {
        $this->logCurrentRequest();
        $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.privates.index'))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function user_can_view_paginated_private_threads()
    {
        $this->logCurrentRequest();
        $this->createPrivateThread($this->tippin, $this->doe);
        $this->createPrivateThread($this->tippin, UserModel::factory()->create());
        $thread = $this->createPrivateThread($this->tippin, $this->developers);
        $this->createPrivateThread($this->tippin, UserModel::factory()->create());
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.privates.page', [
            'private' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function creating_private_thread_with_non_friend_is_pending()
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ])
            ->assertSuccessful()
            ->assertJson([
                'type' => 1,
                'type_verbose' => 'PRIVATE',
                'pending' => true,
                'group' => false,
                'unread' => true,
                'name' => 'John Doe',
                'options' => [
                    'awaiting_my_approval' => false,
                ],
            ]);
    }

    /** @test */
    public function creating_private_thread_with_friend_is_not_pending()
    {
        $this->logCurrentRequest();
        $this->createFriends($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ])
            ->assertSuccessful()
            ->assertJson([
                'pending' => false,
            ]);
    }

    /** @test */
    public function creating_new_private_thread_with_image()
    {
        $this->logCurrentRequest('IMAGE');
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'image' => UploadedFile::fake()->image('picture.jpg'),
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function creating_new_private_thread_with_document()
    {
        $this->logCurrentRequest('DOCUMENT');
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function creating_new_private_thread_with_audio()
    {
        $this->logCurrentRequest('AUDIO');
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function creating_new_private_thread_with_video()
    {
        $this->logCurrentRequest('VIDEO');
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'video' => UploadedFile::fake()->create('test.mov', 500, 'video/quicktime'),
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function creating_new_private_forbidden_when_one_exist()
    {
        $this->logCurrentRequest();
        $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->doe->getKey(),
        ])
            ->assertForbidden();
    }

    /**
     * @test
     *
     * @dataProvider messageFailsValidation
     *
     * @param  $messageValue
     */
    public function create_new_private_fails_validating_message($messageValue)
    {
        $this->logCurrentRequest();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => $messageValue,
            'recipient_alias' => 'user',
            'recipient_id' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    /**
     * @test
     *
     * @dataProvider recipientFailsValidation
     *
     * @param  $aliasValue
     * @param  $idValue
     * @param  $errors
     */
    public function create_new_private_fails_validating_recipient_values($aliasValue, $idValue, $errors)
    {
        $this->logCurrentRequest();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello!',
            'recipient_alias' => $aliasValue,
            'recipient_id' => $idValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors($errors);
    }

    public static function messageFailsValidation(): array
    {
        return [
            'Message cannot be empty' => [''],
            'Message cannot be integers' => [5],
            'Message cannot be boolean' => [true],
            'Message cannot be null' => [null],
            'Message cannot be an array' => [[1, 2]],
            'Message cannot be greater than 5000 characters' => [str_repeat('X', 5001)],
        ];
    }

    public static function recipientFailsValidation(): array
    {
        return [
            'Alias and ID cannot be empty' => ['', '', ['recipient_alias', 'recipient_id']],
            'Alias and ID cannot be boolean' => [true, true, ['recipient_alias', 'recipient_id']],
            'Alias and ID cannot be null' => [null, null, ['recipient_alias', 'recipient_id']],
            'Alias must be string' => [5, 1, ['recipient_alias']],
            'ID cannot be array' => ['user', [1, 2], ['recipient_id']],
        ];
    }
}
