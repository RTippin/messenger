<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class GroupThreadAvatarTest extends HttpTestCase
{
    /** @test */
    public function non_admin_forbidden_to_update_group_avatar()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.avatar.store', [
            'thread' => $thread->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_forbidden_to_destroy_group_avatar()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create(['image' => 'avatar.jpg']);
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.avatar.destroy', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_update_group_avatar_when_thread_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.avatar.store', [
            'thread' => $thread->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_store_group_avatar()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.avatar.store', [
            'thread' => $thread->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function forbidden_to_store_group_avatar_when_disabled_in_config()
    {
        Messenger::setThreadAvatars(false);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.avatar.store', [
            'thread' => $thread->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_destroy_group_avatar()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.avatar.destroy', [
            'thread' => $thread->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function forbidden_to_destroy_group_avatar_when_disabled_in_config()
    {
        Messenger::setThreadAvatars(false);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.avatar.destroy', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function group_avatar_mime_types_can_be_overwritten()
    {
        Messenger::setAvatarMimeTypes('cr2');
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.avatar.store', [
            'thread' => $thread->id,
        ]), [
            'image' => UploadedFile::fake()->create('avatar.cr2', 500, 'image/x-canon-cr2'),
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function group_avatar_size_limit_can_be_overwritten()
    {
        Messenger::setAvatarSizeLimit(20480);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.avatar.store', [
            'thread' => $thread->id,
        ]), [
            'image' => UploadedFile::fake()->create('avatar.jpg', 18000, 'image/jpeg'),
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     *
     * @dataProvider avatarPassesValidation
     *
     * @param  $avatarValue
     */
    public function group_avatar_upload_passes_validations($avatarValue)
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.avatar.store', [
            'thread' => $thread->id,
        ]), [
            'image' => $avatarValue,
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     *
     * @dataProvider avatarFailedValidation
     *
     * @param  $avatarValue
     */
    public function group_avatar_upload_fails_validations($avatarValue)
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.avatar.store', [
            'thread' => $thread->id,
        ]), [
            'image' => $avatarValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public static function avatarPassesValidation(): array
    {
        return [
            'Avatar can be jpeg' => [UploadedFile::fake()->create('image.jpeg', 500, 'image/jpeg')],
            'Avatar can be png' => [UploadedFile::fake()->create('image.png', 500, 'image/png')],
            'Avatar can be bmp' => [UploadedFile::fake()->create('image.bmp', 500, 'image/bmp')],
            'Avatar can be gif' => [UploadedFile::fake()->create('image.gif', 500, 'image/gif')],
            'Avatar can be webp' => [UploadedFile::fake()->create('image.svg', 500, 'image/webp')],
            'Avatar can be 5120 kb max limit' => [UploadedFile::fake()->create('image.jpg', 5120, 'image/jpeg')],
        ];
    }

    public static function avatarFailedValidation(): array
    {
        return [
            'Avatar cannot be empty' => [''],
            'Avatar cannot be integer' => [5],
            'Avatar cannot be null' => [null],
            'Avatar cannot be an array' => [[1, 2]],
            'Avatar cannot be a movie' => [UploadedFile::fake()->create('movie.mov', 500, 'video/quicktime')],
            'Avatar must be 5120 kb or less' => [UploadedFile::fake()->create('image.jpg', 5121, 'image/jpeg')],
            'Avatar cannot be a pdf' => [UploadedFile::fake()->create('test.pdf', 500, 'application/pdf')],
            'Avatar cannot be text file' => [UploadedFile::fake()->create('test.txt', 500, 'text/plain')],
            'Avatar cannot be svg' => [UploadedFile::fake()->create('image.svg', 500, 'image/svg+xml')],
        ];
    }
}
