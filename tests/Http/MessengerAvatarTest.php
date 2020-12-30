<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessengerAvatarTest extends FeatureTestCase
{
    /** @test */
    public function user_can_upload_avatar()
    {
        Storage::fake(Messenger::getAvatarStorage('disk'));

        $tippin = $this->userTippin();

        $directory = Messenger::getAvatarStorage('directory').'/user/'.$tippin->getKey();

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertSuccessful();

        Storage::disk(Messenger::getAvatarStorage('disk'))
            ->assertExists($directory.'/'.$tippin->picture);
    }

    /** @test */
    public function user_can_remove_avatar()
    {
        Storage::fake(Messenger::getAvatarStorage('disk'));

        $tippin = $this->userTippin();

        $tippin->update([
            'picture' => 'avatar.jpg',
        ]);

        $directory = Messenger::getAvatarStorage('directory').'/user/'.$tippin->getKey();

        UploadedFile::fake()
            ->image('avatar.jpg')
            ->storeAs($directory, 'avatar.jpg', [
                'disk' => Messenger::getAvatarStorage('disk'),
            ]);

        Storage::disk(Messenger::getAvatarStorage('disk'))
            ->assertExists($directory.'/avatar.jpg');

        $this->actingAs($tippin);

        $this->deleteJson(route('api.messenger.avatar.destroy'))
            ->assertSuccessful();

        Storage::disk(Messenger::getAvatarStorage('disk'))
            ->assertMissing($directory.'/avatar.jpg');

        $this->assertNull($tippin->picture);
    }

    /** @test */
    public function avatar_upload_validation_checks_size_and_mime()
    {
        $this->actingAs($this->userTippin());

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
