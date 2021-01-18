<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Messenger\StoreMessengerAvatar;
use RTippin\Messenger\Contracts\MessengerProvider;
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
    public function upload_avatar_updates_provider()
    {
        $this->assertNull($this->tippin->picture);

        app(StoreMessengerAvatar::class)->execute([
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $this->assertNotNull($this->tippin->picture);
    }

    /** @test */
    public function upload_avatar_stores_image()
    {
        app(StoreMessengerAvatar::class)->execute([
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        Storage::disk($this->disk)->assertExists($this->directory.'/'.$this->tippin->picture);
    }

    /** @test */
    public function avatar_upload_removes_existing_avatar()
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
