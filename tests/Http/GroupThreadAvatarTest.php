<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Broadcasting\ThreadAvatarBroadcast;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Events\ThreadAvatarEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

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
        $users = UserModel::all();

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
                'owner_id' => 1,
                'owner_type' => self::UserModelType,
            ]));

        $this->group->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => 2,
                'owner_type' => self::UserModelType,
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

    /** @test */
    public function non_admin_forbidden_to_update_group_avatar()
    {
        $this->actingAs(UserModel::find(2));

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
        $this->actingAs(UserModel::find(1));

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

        $this->actingAs(UserModel::find(1));

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
        $this->expectsEvents([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        $this->actingAs(UserModel::find(1));

        $this->assertEquals('5.png', $this->group->image);

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'default' => '1.png',
        ])
            ->assertSuccessful();

        $this->assertEquals('1.png', $this->group->fresh()->image);
    }

    /** @test */
    public function group_avatar_upload_validation_checks_size_and_mime()
    {
        $this->actingAs(UserModel::find(1));

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
        $this->expectsEvents([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        Storage::fake(Messenger::getThreadStorage('disk'));

        $this->actingAs(UserModel::find(1));

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertSuccessful();

        Storage::disk(Messenger::getThreadStorage('disk'))
            ->assertExists($this->group->fresh()->getAvatarPath());
    }

    /** @test */
    public function group_avatar_upload_stores_photo_and_removes_old()
    {
        $this->expectsEvents([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        $this->setupGroupAvatar();

        $this->actingAs(UserModel::find(1));

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar2.jpg'),
        ])
            ->assertSuccessful();

        Storage::disk(Messenger::getThreadStorage('disk'))
            ->assertExists($this->group->fresh()->getAvatarPath());

        Storage::disk(Messenger::getAvatarStorage('disk'))
            ->assertMissing($this->group->getStorageDirectory().'/avatar/avatar.jpg');

        $this->assertNotEquals('avatar.jpg', $this->group->fresh()->image);
    }

    /** @test */
    public function update_group_avatar_to_default_removes_old()
    {
        $this->expectsEvents([
            ThreadAvatarBroadcast::class,
            ThreadAvatarEvent::class,
        ]);

        $this->setupGroupAvatar();

        $this->actingAs(UserModel::find(1));

        $this->postJson(route('api.messenger.threads.avatar.update', [
            'thread' => $this->group->id,
        ]), [
            'default' => '2.png',
        ])
            ->assertSuccessful();

        Storage::disk(Messenger::getAvatarStorage('disk'))
            ->assertMissing($this->group->getStorageDirectory().'/avatar/avatar.jpg');

        $this->assertEquals('2.png', $this->group->fresh()->image);
    }
}
