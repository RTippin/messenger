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
        $disk = Messenger::getAvatarStorage('disk');

        Storage::fake($disk);

        $tippin = $this->userTippin();

        $directory = Messenger::getAvatarStorage('directory').'/user/'.$tippin->getKey();

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertSuccessful();

        Storage::disk($disk)->assertExists($directory.'/'.$tippin->picture);
    }

    /** @test */
    public function user_can_remove_avatar()
    {
        $disk = Messenger::getAvatarStorage('disk');

        Storage::fake($disk);

        $tippin = $this->userTippin();

        $tippin->update([
            'picture' => 'avatar.jpg',
        ]);

        $directory = Messenger::getAvatarStorage('directory').'/user/'.$tippin->getKey();

        UploadedFile::fake()
            ->image('avatar.jpg')
            ->storeAs($directory, 'avatar.jpg', [
                'disk' => $disk,
            ]);

        Storage::disk($disk)->assertExists($directory.'/avatar.jpg');

        $this->actingAs($tippin);

        $this->deleteJson(route('api.messenger.avatar.destroy'))
            ->assertSuccessful();

        Storage::disk($disk)->assertMissing($directory.'/avatar.jpg');

        $this->assertNull($tippin->fresh()->picture);
    }

    /**
     * @test
     * @dataProvider avatarFileValidation
     * @param $avatarValue
     */
    public function avatar_upload_checks_size_mime_and_inputs($avatarValue)
    {
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => $avatarValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public function avatarFileValidation(): array
    {
        return [
            'Image cannot be empty' => [''],
            'Image cannot be integer' => [5],
            'Image cannot be null' => [null],
            'Image cannot be an array' => [[1, 2]],
            'Image cannot be a movie' => [UploadedFile::fake()->create('movie.mov', 500, 'video/quicktime')],
            'Image must be under 5mb' => [UploadedFile::fake()->create('image.jpg', 6000, 'image/jpeg')],
            'Image cannot be a pdf' => [UploadedFile::fake()->create('test.pdf', 500, 'application/pdf')],
        ];
    }
}
