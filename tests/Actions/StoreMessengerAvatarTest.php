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

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->directory = Messenger::getAvatarStorage('directory').'/user/'.$this->tippin->getKey();

        Messenger::setProvider($this->tippin);

        Storage::fake(Messenger::getAvatarStorage('disk'));
    }

    /** @test */
    public function upload_avatar_stores_image_and_updates_provider()
    {
        app(StoreMessengerAvatar::class)->execute([
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $this->assertNotNull($this->tippin->picture);

        Storage::disk(Messenger::getAvatarStorage('disk'))
            ->assertExists($this->directory.'/'.$this->tippin->picture);
    }

    /** @test */
    public function avatar_upload_removes_existing_avatar()
    {
        $this->tippin->update([
            'picture' => 'avatar.jpg',
        ]);

        UploadedFile::fake()
            ->image('avatar.jpg')
            ->storeAs($this->directory, 'avatar.jpg', [
                'disk' => Messenger::getAvatarStorage('disk'),
            ]);

        app(StoreMessengerAvatar::class)->execute([
            'image' => UploadedFile::fake()->image('avatar2.jpg'),
        ]);

        $this->assertNotSame('avatar.jpg', $this->tippin->picture);

        Storage::disk(Messenger::getAvatarStorage('disk'))
            ->assertExists($this->directory.'/'.$this->tippin->picture);

        Storage::disk(Messenger::getAvatarStorage('disk'))
            ->assertMissing($this->directory.'/avatar.jpg');
    }
}
