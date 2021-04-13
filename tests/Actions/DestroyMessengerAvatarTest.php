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
    private string $directory;
    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin->update([
            'picture' => 'avatar.jpg',
        ]);
        $this->directory = Messenger::getAvatarStorage('directory').'/user/'.$this->tippin->getKey();
        $this->disk = Messenger::getAvatarStorage('disk');
        Messenger::setProvider($this->tippin);
        Storage::fake($this->disk);
        UploadedFile::fake()->image('avatar.jpg')->storeAs($this->directory, 'avatar.jpg', [
            'disk' => $this->disk,
        ]);
    }

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setProviderAvatarRemoval(false);

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
        app(DestroyMessengerAvatar::class)->execute();

        Storage::disk($this->disk)->assertMissing($this->directory.'/avatar.jpg');
    }
}
