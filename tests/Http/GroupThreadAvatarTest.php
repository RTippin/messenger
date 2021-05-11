<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Broadcasting\ThreadAvatarBroadcast;
use RTippin\Messenger\Events\ThreadAvatarEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class GroupThreadAvatarTest extends FeatureTestCase
{
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);
    }

    /** @test */
    public function non_admin_forbidden_to_update_group_avatar()
    {
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'default' => '1.png',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_update_group_avatar_when_thread_locked()
    {
        $this->group->update([
            'lockout' => true,
        ]);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'default' => '1.png',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function update_group_avatar_without_changes_expects_no_events()
    {
        $this->actingAs($this->tippin);

        $this->doesntExpectEvents([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'default' => '5.png',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function update_group_avatar_with_new_default()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'default' => '1.png',
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas('threads', [
            'id' => $this->group->id,
            'image' => '1.png',
        ]);
    }

    /** @test */
    public function group_avatar_upload_stores_photo()
    {
        Storage::fake(Messenger::getThreadStorage('disk'));
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function group_avatar_mime_types_can_be_overwritten()
    {
        Storage::fake(Messenger::getThreadStorage('disk'));
        Messenger::setThreadAvatarMimeTypes('cr2');
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'image' => UploadedFile::fake()->create('avatar.cr2', 500, 'image/x-canon-cr2'),
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function group_avatar_size_limit_can_be_overwritten()
    {
        Storage::fake(Messenger::getThreadStorage('disk'));
        Messenger::setThreadAvatarSizeLimit(20480);
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'image' => UploadedFile::fake()->create('avatar.jpg', 18000, 'image/jpeg'),
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     * @dataProvider avatarDefaultPassesValidation
     * @param $defaultValue
     */
    public function update_group_avatar_default_passes_validation($defaultValue)
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'default' => $defaultValue,
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     * @dataProvider avatarDefaultFailedValidation
     * @param $defaultValue
     */
    public function update_group_avatar_default_fails_validation($defaultValue)
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'default' => $defaultValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('default');
    }

    /**
     * @test
     * @dataProvider avatarPassesValidation
     * @param $avatarValue
     */
    public function group_avatar_upload_passes_validations($avatarValue)
    {
        Storage::fake(Messenger::getThreadStorage('disk'));
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'image' => $avatarValue,
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     * @dataProvider avatarFailedValidation
     * @param $avatarValue
     */
    public function group_avatar_upload_fails_validations($avatarValue)
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'image' => $avatarValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public function avatarPassesValidation(): array
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

    public function avatarFailedValidation(): array
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

    public function avatarDefaultPassesValidation(): array
    {
        return [
            'Default can be 1.png' => ['1.png'],
            'Default can be 2.png' => ['2.png'],
            'Default can be 3.png' => ['3.png'],
            'Default can be 4.png' => ['4.png'],
            'Default can be 5.png' => ['5.png'],
        ];
    }

    public function avatarDefaultFailedValidation(): array
    {
        return [
            'Default cannot be empty' => [''],
            'Default cannot be integer' => [5],
            'Default cannot be null' => [null],
            'Default cannot be an array' => [[1, 2]],
            'Default cannot be 0.png' => ['0.png'],
            'Default must be between (1-5).png' => ['6.png'],
        ];
    }
}
