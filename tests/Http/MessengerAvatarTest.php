<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\HttpTestCase;

class MessengerAvatarTest extends HttpTestCase
{
    /** @test */
    public function user_forbidden_to_upload_avatar_when_disabled()
    {
        $this->logCurrentRequest();
        Messenger::setProviderAvatars(false);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_can_upload_avatar()
    {
        $this->logCurrentRequest();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function avatar_mime_types_can_be_overwritten()
    {
        Messenger::setAvatarMimeTypes('cr2');
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => UploadedFile::fake()->create('avatar.cr2', 500, 'image/x-canon-cr2'),
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function avatar_size_limit_can_be_overwritten()
    {
        Messenger::setAvatarSizeLimit(20480);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => UploadedFile::fake()->create('avatar.jpg', 18000, 'image/jpeg'),
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function user_can_remove_avatar()
    {
        $this->logCurrentRequest();
        $this->tippin->update([
            'picture' => 'avatar.jpg',
        ]);
        $directory = Messenger::getAvatarStorage('directory').'/user/'.$this->tippin->getKey();
        UploadedFile::fake()->image('avatar.jpg')->storeAs($directory, 'avatar.jpg', [
            'disk' => Messenger::getAvatarStorage('disk'),
        ]);
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.avatar.destroy'))
            ->assertStatus(204);
    }

    /** @test */
    public function user_forbidden_to_remove_avatar_when_disabled()
    {
        $this->logCurrentRequest();
        Messenger::setProviderAvatars(false);
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.avatar.destroy'))
            ->assertForbidden();
    }

    /**
     * @test
     *
     * @dataProvider avatarPassesValidation
     *
     * @param  $avatarValue
     */
    public function avatar_upload_passes_validation($avatarValue)
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => $avatarValue,
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     *
     * @dataProvider avatarFailedValidation
     *
     * @param  $avatarValue
     */
    public function avatar_upload_fails_validation($avatarValue)
    {
        $this->logCurrentRequest();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.avatar.update'), [
            'image' => $avatarValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public static function avatarFailedValidation(): array
    {
        return [
            'Avatar cannot be empty' => [''],
            'Avatar cannot be integer' => [5],
            'Avatar cannot be null' => [null],
            'Avatar cannot be an array' => [[1, 2]],
            'Avatar cannot be a movie' => [UploadedFile::fake()->create('movie.mov', 500, 'video/quicktime')],
            'Avatar must be 5120 kb or less' => [UploadedFile::fake()->create('image.jpg', 5121, 'image/jpeg')],
            'Avatar cannot be a pdf' => [UploadedFile::fake()->create('test.pdf', 500, 'application/pdf')],
            'Avatar cannot be text file' => [UploadedFile::fake()->create('test.txt', 500, 'text/plain')],
            'Avatar cannot be svg' => [UploadedFile::fake()->create('image.svg', 500, 'image/svg+xml')],
        ];
    }

    public static function avatarPassesValidation(): array
    {
        return [
            'Avatar can be jpeg' => [UploadedFile::fake()->create('image.jpeg', 500, 'image/jpeg')],
            'Avatar can be png' => [UploadedFile::fake()->create('image.png', 500, 'image/png')],
            'Avatar can be bmp' => [UploadedFile::fake()->create('image.bmp', 500, 'image/bmp')],
            'Avatar can be gif' => [UploadedFile::fake()->create('image.gif', 500, 'image/gif')],
            'Avatar can be webp' => [UploadedFile::fake()->create('image.webp', 500, 'image/webp')],
            'Avatar can be 5120 kb max limit' => [UploadedFile::fake()->create('image.jpg', 5120, 'image/jpeg')],
        ];
    }
}
