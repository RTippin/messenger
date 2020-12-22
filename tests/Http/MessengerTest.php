<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Events\StatusHeartbeatEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

class MessengerTest extends FeatureTestCase
{
    /** @test */
    public function test_guest_is_unauthorized()
    {
        $this->get(route('api.messenger.info'))
            ->assertUnauthorized();
    }

    /** @test */
    public function test_messenger_info_was_successful()
    {
        $this->actingAs(UserModel::first());

        $this->get(route('api.messenger.info'))
            ->assertSuccessful()
            ->assertJson([
                'siteName' => 'Messenger-Testbench',
                'messageImageUpload' => true,
                'calling' => false,
                'threadsIndexCount' => 100,
            ]);
    }

    /** @test */
    public function test_messenger_info_changes_when_set_dynamically()
    {
        $this->actingAs(UserModel::first());

        $this->get(route('api.messenger.info'))
            ->assertSuccessful()
            ->assertJson([
                'siteName' => 'Messenger-Testbench',
                'messageImageUpload' => true,
                'calling' => false,
                'threadsIndexCount' => 100,
            ]);

        Messenger::setCalling(true);
        Messenger::setMessageImageUpload(false);
        Messenger::setThreadsIndexCount(50);

        $this->get(route('api.messenger.info'))
            ->assertSuccessful()
            ->assertJson([
                'siteName' => 'Messenger-Testbench',
                'messageImageUpload' => false,
                'calling' => true,
                'threadsIndexCount' => 50,
            ]);
    }

    /** @test */
    public function test_messenger_created_when_called_from_user_without_messenger()
    {
        $user = UserModel::create([
            'name' => 'Jane Smith',
            'email' => 'smith@example.net',
            'password' => 'secret',
        ]);

        $this->assertDatabaseMissing('messengers', [
            'owner_id' => $user->getKey(),
        ]);

        $this->actingAs($user);

        $this->get(route('api.messenger.settings'))
            ->assertSuccessful()
            ->assertJson([
                'owner_id' => $user->getKey(),
                'dark_mode' => true,
            ]);

        $this->assertDatabaseHas('messengers', [
            'owner_id' => $user->getKey(),
        ]);
    }

    /** @test */
    public function test_updating_messenger_settings_validations()
    {
        $user = UserModel::first();

        $this->actingAs($user);

        $this->putJson(route('api.messenger.settings'), [
            'message_popups' => 'invalid',
            'message_sound' => false,
            'call_ringtone_sound' => 66,
            'notify_sound' => false,
            'dark_mode' => false,
            'online_status' => 5,
        ])
            ->assertJsonValidationErrors([
                'message_popups',
                'call_ringtone_sound',
                'online_status',
            ]);
    }

    /** @test */
    public function test_updating_messenger_settings()
    {
        $user = UserModel::first();

        $this->actingAs($user);

        $this->putJson(route('api.messenger.settings'), [
            'message_popups' => false,
            'message_sound' => false,
            'call_ringtone_sound' => false,
            'notify_sound' => false,
            'dark_mode' => false,
            'online_status' => 2,
        ])
            ->assertSuccessful()
            ->assertJson([
                'message_popups' => false,
                'message_sound' => false,
                'call_ringtone_sound' => false,
                'notify_sound' => false,
                'dark_mode' => false,
                'online_status' => 2,
            ]);

        $this->assertEquals(2, $user->onlineStatus());
    }

    /** @test */
    public function test_user_can_upload_avatar()
    {
        Storage::fake(Messenger::getAvatarStorage('disk'));

        $user = UserModel::first();

        $directory = Messenger::getAvatarStorage('directory')."/user/{$user->getKey()}";

        $this->actingAs($user);

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertSuccessful();

        Storage::disk(Messenger::getAvatarStorage('disk'))
            ->assertExists($directory.'/'.$user->picture);
    }

    /** @test */
    public function test_user_can_remove_avatar()
    {
        Storage::fake(Messenger::getAvatarStorage('disk'));

        $user = UserModel::first();

        $user->picture = 'avatar.jpg';

        $user->save();

        $directory = Messenger::getAvatarStorage('directory')."/user/{$user->getKey()}";

        UploadedFile::fake()
            ->image('avatar.jpg')
            ->storeAs($directory, 'avatar.jpg', [
                'disk' => Messenger::getAvatarStorage('disk'),
            ]);

        Storage::disk(Messenger::getAvatarStorage('disk'))
            ->assertExists($directory.'/avatar.jpg');

        $this->actingAs($user);

        $this->deleteJson(route('api.messenger.avatar.destroy'))
            ->assertSuccessful();

        Storage::disk(Messenger::getAvatarStorage('disk'))
            ->assertMissing($directory.'/avatar.jpg');

        $this->assertNull($user->picture);
    }

    /** @test */
    public function test_avatar_upload_validation_checks_size_and_mime()
    {
        $user = UserModel::first();

        $this->actingAs($user);

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => UploadedFile::fake()->create('movie.mov', 5000000, 'video/quicktime'),
        ])
            ->assertJsonValidationErrors('image');

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => UploadedFile::fake()->create('image.jpg', 5000000, 'image/jpeg'),
        ])
            ->assertJsonValidationErrors('image');
    }

    /** @test */
    public function test_messenger_heartbeat_online()
    {
        $this->expectsEvents([
            StatusHeartbeatEvent::class,
        ]);

        $user = UserModel::first();

        $this->actingAs($user);

        $this->postJson(route('api.messenger.heartbeat'), [
            'away' => false,
        ])
            ->assertSuccessful();

        $this->assertEquals(1, $user->onlineStatus());
    }

    /** @test */
    public function test_messenger_heartbeat_away()
    {
        $this->expectsEvents([
            StatusHeartbeatEvent::class,
        ]);

        $user = UserModel::first();

        $this->actingAs($user);

        $this->postJson(route('api.messenger.heartbeat'), [
            'away' => true,
        ])
            ->assertSuccessful();

        $this->assertEquals(2, $user->onlineStatus());
    }
}
