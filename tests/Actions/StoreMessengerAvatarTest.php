<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Messenger\StoreMessengerAvatar;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreMessengerAvatarTest extends FeatureTestCase
{
    private MessengerProvider $tippin;
    private string $directory;
    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->directory = Messenger::getAvatarStorage('directory').'/user/'.$this->tippin->getKey();
        $this->disk = Messenger::getAvatarStorage('disk');
        Messenger::setProvider($this->tippin);
        Storage::fake($this->disk);
    }

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setProviderAvatarUpload(false);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Avatar upload is currently disabled.');

        app(StoreMessengerAvatar::class)->execute([
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ]);
    }

    /** @test */
    public function it_updates_provider_picture()
    {
        $updated = now()->addMinutes(5);
        Carbon::setTestNow($updated);

        app(StoreMessengerAvatar::class)->execute([
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->tippin->getKey(),
            'updated_at' => $updated,
        ]);
        $this->assertNotNull($this->tippin->picture);
    }

    /** @test */
    public function it_stores_image()
    {
        app(StoreMessengerAvatar::class)->execute([
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        Storage::disk($this->disk)->assertExists($this->directory.'/'.$this->tippin->picture);
    }

    /** @test */
    public function it_removes_existing_avatar_from_disk()
    {
        $this->tippin->update([
            'picture' => 'avatar.jpg',
        ]);
        UploadedFile::fake()->image('avatar.jpg')->storeAs($this->directory, 'avatar.jpg', [
            'disk' => $this->disk,
        ]);

        app(StoreMessengerAvatar::class)->execute([
            'image' => UploadedFile::fake()->image('avatar2.jpg'),
        ]);

        $this->assertNotSame('avatar.jpg', $this->tippin->picture);
        Storage::disk($this->disk)->assertExists($this->directory.'/'.$this->tippin->picture);
        Storage::disk($this->disk)->assertMissing($this->directory.'/avatar.jpg');
    }
}
