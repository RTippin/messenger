<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessengerAvatarTest extends FeatureTestCase
{
    private MessengerProvider $tippin;
    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->disk = Messenger::getAvatarStorage('disk');
        Storage::fake($this->disk);
    }

    /** @test */
    public function user_forbidden_to_upload_avatar_when_disabled()
    {
        Messenger::setProviderAvatarUpload(false);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_can_upload_avatar()
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function user_can_remove_avatar()
    {
        $this->tippin->update([
            'picture' => 'avatar.jpg',
        ]);
        $directory = Messenger::getAvatarStorage('directory').'/user/'.$this->tippin->getKey();
        UploadedFile::fake()->image('avatar.jpg')->storeAs($directory, 'avatar.jpg', [
            'disk' => $this->disk,
        ]);
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.avatar.destroy'))
            ->assertSuccessful();
    }

    /** @test */
    public function user_forbidden_to_remove_avatar_when_disabled()
    {
        Messenger::setProviderAvatarRemoval(false);
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.avatar.destroy'))
            ->assertForbidden();
    }

    /**
     * @test
     * @dataProvider avatarFileValidation
     * @param $avatarValue
     */
    public function avatar_upload_checks_size_mime_and_inputs($avatarValue)
    {
        $this->actingAs($this->tippin);

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
