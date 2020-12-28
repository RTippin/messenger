<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Broadcasting\ThreadAvatarBroadcast;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Events\ThreadAvatarEvent;
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
                'owner_id' => $users->first()->getKey(),
                'owner_type' => get_class($users->first()),
            ]));

        $this->group->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => $users->last()->getKey(),
                'owner_type' => get_class($users->last()),
            ]));
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
        $this->actingAs(UserModel::first());

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

        $this->actingAs(UserModel::first());

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

        $this->actingAs(UserModel::first());

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
        $this->actingAs(UserModel::first());

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
}
