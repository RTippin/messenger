<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread(
            $this->userTippin(),
            $this->userDoe()
        );
    }

    /** @test */
    public function non_admin_forbidden_to_update_group_avatar()
    {
        $this->actingAs($this->userDoe());

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

        $this->actingAs($this->userTippin());

        $this->assertEquals('5.png', $this->group->image);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'default' => '5.png',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function update_group_avatar_with_new_default_expects_events()
    {
        $tippin = $this->userTippin();

        Event::fake([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->assertEquals('5.png', $this->group->image);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'default' => '1.png',
        ])
            ->assertSuccessful();

        $this->assertEventsDispatched($tippin);

        $this->assertEquals('1.png', $this->group->fresh()->image);
    }

    /** @test */
    public function group_avatar_upload_stores_photo_when_previously_default()
    {
        $tippin = $this->userTippin();

        $disk = Messenger::getThreadStorage('disk');

        Event::fake([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        Storage::fake($disk);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertSuccessful();

        $this->assertEventsDispatched($tippin);

        Storage::disk($disk)->assertExists($this->group->fresh()->getAvatarPath());
    }

    /** @test */
    public function group_avatar_upload_stores_photo_and_removes_old()
    {
        $tippin = $this->userTippin();

        Event::fake([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        $this->setupGroupAvatar();

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar2.jpg'),
        ])
            ->assertSuccessful();

        $this->assertEventsDispatched($tippin);

        Storage::disk(Messenger::getThreadStorage('disk'))
            ->assertExists($this->group->fresh()->getAvatarPath());

        Storage::disk(Messenger::getAvatarStorage('disk'))
            ->assertMissing($this->group->getStorageDirectory().'/avatar/avatar.jpg');

        $this->assertNotEquals('avatar.jpg', $this->group->fresh()->image);
    }

    /** @test */
    public function update_group_avatar_to_default_removes_old()
    {
        $tippin = $this->userTippin();

        Event::fake([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        $this->setupGroupAvatar();

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'default' => '2.png',
        ])
            ->assertSuccessful();

        $this->assertEventsDispatched($tippin);

        Storage::disk(Messenger::getAvatarStorage('disk'))
            ->assertMissing($this->group->getStorageDirectory().'/avatar/avatar.jpg');

        $this->assertEquals('2.png', $this->group->fresh()->image);
    }

    /**
     * @test
     * @dataProvider avatarDefaultValidation
     * @param $defaultValue
     */
    public function update_group_avatar_default_checks_values($defaultValue)
    {
        $this->actingAs($this->userTippin());

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
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'image' => $avatarValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    private function setupGroupAvatar(): void
    {
        $disk = Messenger::getThreadStorage('disk');

        Storage::fake($disk);

        $this->group->update([
            'image' => 'avatar.jpg',
        ]);

        UploadedFile::fake()
            ->image('avatar.jpg')
            ->storeAs($this->group->getStorageDirectory().'/avatar', 'avatar.jpg', [
                'disk' => $disk,
            ]);

        Storage::disk($disk)->assertExists($this->group->getAvatarPath());
    }

    private function assertEventsDispatched(MessengerProvider $provider): void
    {
        Event::assertDispatched(function (ThreadAvatarBroadcast $event) {
            $this->assertContains('First Test Group', $event->broadcastWith());
            $this->assertContains('presence-thread.'.$this->group->id, $event->broadcastOn());

            return true;
        });

        Event::assertDispatched(function (ThreadAvatarEvent $event) use ($provider) {
            $this->assertEquals($provider->getKey(), $event->provider->getKey());
            $this->assertEquals($this->group->id, $event->thread->id);

            return true;
        });
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
