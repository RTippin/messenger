<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Broadcasting\ThreadAvatarBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Definitions;
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

        $this->setupInitialGroup();
    }

    private function setupInitialGroup(): void
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->group = Thread::create([
            'type' => 2,
            'subject' => 'First Test Group',
            'image' => '5.png',
            'add_participants' => true,
            'invitations' => true,
            'calling' => true,
            'knocks' => true,
        ]);

        $this->group->participants()
            ->create(array_merge(Definitions::DefaultAdminParticipant, [
                'owner_id' => $tippin->getKey(),
                'owner_type' => get_class($tippin),
            ]));

        $this->group->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => $doe->getKey(),
                'owner_type' => get_class($doe),
            ]));
    }

    private function setupGroupAvatar(): void
    {
        Storage::fake(Messenger::getThreadStorage('disk'));

        $this->group->image = 'avatar.jpg';

        $this->group->save();

        UploadedFile::fake()
            ->image('avatar.jpg')
            ->storeAs($this->group->getStorageDirectory().'/avatar', 'avatar.jpg', [
                'disk' => Messenger::getThreadStorage('disk'),
            ]);

        Storage::disk(Messenger::getThreadStorage('disk'))
            ->assertExists($this->group->getAvatarPath());
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

    /** @test */
    public function non_admin_forbidden_to_update_group_avatar()
    {
        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.show', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'default' => '1.png',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function update_group_avatar_validates_invalid_defaults()
    {
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'default' => '7.png',
        ])
            ->assertJsonValidationErrors('default');

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'default' => null,
        ])
            ->assertJsonValidationErrors('default');

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]))
            ->assertJsonValidationErrors([
                'default',
                'image',
            ]);
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
    public function group_avatar_upload_validation_checks_size_and_mime()
    {
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'image' => UploadedFile::fake()->create('movie.mov', 5000000, 'video/quicktime'),
        ])
            ->assertJsonValidationErrors('image');

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'image' => UploadedFile::fake()->create('image.jpg', 5000000, 'image/jpeg'),
        ])
            ->assertJsonValidationErrors('image');
    }

    /** @test */
    public function group_avatar_upload_stores_photo_when_previously_default()
    {
        $tippin = $this->userTippin();

        Event::fake([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        Storage::fake(Messenger::getThreadStorage('disk'));

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertSuccessful();

        $this->assertEventsDispatched($tippin);

        Storage::disk(Messenger::getThreadStorage('disk'))
            ->assertExists($this->group->fresh()->getAvatarPath());
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
}
