<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Messenger\DestroyMessengerAvatar;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class DestroyMessengerAvatarTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
        $this->tippin->update([
            'picture' => 'avatar.jpg',
        ]);
    }

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setProviderAvatars(false);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Avatar removal is currently disabled.');

        app(DestroyMessengerAvatar::class)->execute();
    }

    /** @test */
    public function it_updates_provider_picture()
    {
        app(DestroyMessengerAvatar::class)->execute();

        $this->assertDatabaseHas('users', [
            'id' => $this->tippin->getKey(),
            'picture' => null,
        ]);
    }

    /** @test */
    public function it_removes_image_from_disk()
    {
        $directory = Messenger::getAvatarStorage('directory').'/user/'.$this->tippin->getKey();

        UploadedFile::fake()->image('avatar.jpg')->storeAs($directory, 'avatar.jpg', [
            'disk' => 'public',
        ]);

        app(DestroyMessengerAvatar::class)->execute();

        Storage::disk('public')->assertMissing($directory.'/avatar.jpg');
    }
}
