<?php

namespace RTippin\Messenger\Tests\Actions;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RTippin\Messenger\Actions\Messenger\StoreMessengerAvatar;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Services\FileService;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreMessengerAvatarTest extends FeatureTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = Messenger::getAvatarStorage('directory').'/user/'.$this->tippin->getKey();
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setProviderAvatars(false);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Avatar upload is currently disabled.');

        app(StoreMessengerAvatar::class)->execute(UploadedFile::fake()->image('avatar.jpg'));
    }

    /** @test */
    public function it_throws_exception_if_transaction_fails_and_removes_uploaded_avatar()
    {
        $this->tippin->update(['picture' => 'avatar.jpg']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Storage Error');
        $fileService = $this->mock(FileService::class);
        $fileService->shouldReceive([
            'setType' => Mockery::self(),
            'setDisk' => Mockery::self(),
            'setDirectory' => Mockery::self(),
            'upload' => 'avatar.jpg',
        ]);
        $fileService->shouldReceive('destroy')->andThrow(new Exception('Storage Error'));

        app(StoreMessengerAvatar::class)->execute(UploadedFile::fake()->image('avatar.jpg'));
    }

    /** @test */
    public function it_updates_provider_picture()
    {
        Carbon::setTestNow($updated = now()->addMinutes(5));

        app(StoreMessengerAvatar::class)->execute(UploadedFile::fake()->image('avatar.jpg'));

        $this->assertDatabaseHas('users', [
            'id' => $this->tippin->getKey(),
            'updated_at' => $updated,
        ]);
        $this->assertNotNull($this->tippin->picture);
    }

    /** @test */
    public function it_stores_image()
    {
        app(StoreMessengerAvatar::class)->execute(UploadedFile::fake()->image('avatar.jpg'));

        Storage::disk('public')->assertExists($this->directory.'/'.$this->tippin->picture);
    }

    /** @test */
    public function it_removes_existing_avatar_from_disk()
    {
        $this->tippin->update([
            'picture' => 'avatar.jpg',
        ]);
        UploadedFile::fake()->image('avatar.jpg')->storeAs($this->directory, 'avatar.jpg', [
            'disk' => 'public',
        ]);

        app(StoreMessengerAvatar::class)->execute(UploadedFile::fake()->image('avatar.jpg'));

        $this->assertNotSame('avatar.jpg', $this->tippin->picture);
        Storage::disk('public')->assertExists($this->directory.'/'.$this->tippin->picture);
        Storage::disk('public')->assertMissing($this->directory.'/avatar.jpg');
    }
}
