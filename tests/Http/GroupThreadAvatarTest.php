<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Broadcasting\ThreadAvatarBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ThreadAvatarEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class GroupThreadAvatarTest extends FeatureTestCase
{
    private Thread $group;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

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
        $this->doesntExpectEvents([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        $this->actingAs($this->tippin);

        $this->assertSame('5.png', $this->group->image);

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
        $this->expectsEvents([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        $this->actingAs($this->tippin);

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
        $this->expectsEvents([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        Storage::fake(Messenger::getThreadStorage('disk'));

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     * @dataProvider avatarDefaultValidation
     * @param $defaultValue
     */
    public function update_group_avatar_default_checks_values($defaultValue)
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
     * @dataProvider avatarFileValidation
     * @param $avatarValue
     */
    public function group_avatar_upload_checks_size_and_mime($avatarValue)
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

    public function avatarFileValidation(): array
    {
        return [
            'Avatar cannot be empty' => [''],
            'Avatar cannot be integer' => [5],
            'Avatar cannot be null' => [null],
            'Avatar cannot be an array' => [[1, 2]],
            'Avatar cannot be a movie' => [UploadedFile::fake()->create('movie.mov', 500, 'video/quicktime')],
            'Avatar must be under 5mb' => [UploadedFile::fake()->create('image.jpg', 6000, 'image/jpeg')],
            'Avatar cannot be a pdf' => [UploadedFile::fake()->create('test.pdf', 500, 'application/pdf')],
        ];
    }

    public function avatarDefaultValidation(): array
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
