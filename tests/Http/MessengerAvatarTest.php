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

    /**
     * @test
     * @dataProvider avatarValidation
     * @param $avatarValue
     */
    public function avatar_upload_validates_request($avatarValue)
    {
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => $avatarValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public function avatarValidation(): array
    {
        return [
            'Image cannot be empty' => [''],
            'Image cannot be integer' => [5],
            'Image cannot be null' => [null],
            'Image cannot be an array' => [[1,2]],
            'Image must be image format' => [UploadedFile::fake()->create('movie.mov', 5000000, 'video/quicktime')],
            'Image must be under 5mb' => [UploadedFile::fake()->create('image.jpg', 5000000, 'image/jpeg')],
            'Image cannot be a pdf' => [UploadedFile::fake()->create('test.pdf', 5000, 'application/pdf')],
        ];
    }
}
