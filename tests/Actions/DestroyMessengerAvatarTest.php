<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Messenger\DestroyMessengerAvatar;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class DestroyMessengerAvatarTest extends FeatureTestCase
{
    /** @test */
    public function destroy_avatar_removes_file_and_updates_provider()
    {
        $tippin = $this->userTippin();

        $directory = Messenger::getAvatarStorage('directory').'/user/'.$tippin->getKey();

        Messenger::setProvider($tippin);

        Storage::fake(Messenger::getAvatarStorage('disk'));

        $tippin->update([
            'picture' => 'avatar.jpg',
        ]);

        UploadedFile::fake()
            ->image('avatar.jpg')
            ->storeAs($directory, 'avatar.jpg', [
                'disk' => Messenger::getAvatarStorage('disk'),
            ]);

        app(DestroyMessengerAvatar::class)->execute();

        $this->assertNull($tippin->picture);

        Storage::disk(Messenger::getAvatarStorage('disk'))
            ->assertMissing($directory.'/avatar.jpg');
    }
}
