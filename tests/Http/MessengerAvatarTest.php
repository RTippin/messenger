<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\stubs\UserModel;

class MessengerAvatarTest extends FeatureTestCase
{
    /** @test */
    public function user_can_upload_avatar()
    {
        Storage::fake(Messenger::getAvatarStorage('disk'));

        $user = UserModel::find(1);

        $directory = Messenger::getAvatarStorage('directory').'/user/1';

        $this->actingAs($user);

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertSuccessful();

        Storage::disk(Messenger::getAvatarStorage('disk'))
            ->assertExists($directory.'/'.$user->picture);
    }

    /** @test */
    public function user_can_remove_avatar()
    {
        Storage::fake(Messenger::getAvatarStorage('disk'));

        $user = UserModel::find(1);

        $user->picture = 'avatar.jpg';

        $user->save();

        $directory = Messenger::getAvatarStorage('directory').'/user/1';

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
    public function avatar_upload_validation_checks_size_and_mime()
    {
        $this->actingAs(UserModel::find(1));

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => UploadedFile::fake()->create('movie.mov', 5000000, 'video/quicktime'),
        ])
            ->assertJsonValidationErrors('image');

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => UploadedFile::fake()->create('image.jpg', 5000000, 'image/jpeg'),
        ])
            ->assertJsonValidationErrors('image');
    }
}
