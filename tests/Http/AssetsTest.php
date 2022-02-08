<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\Fixtures\FunBotPackage;
use RTippin\Messenger\Tests\Fixtures\SillyBotPackage;
use RTippin\Messenger\Tests\HttpTestCase;

class AssetsTest extends HttpTestCase
{
    /** @test */
    public function it_renders_provider_avatar()
    {
        $this->logCurrentRequest();
        $this->tippin->update(['picture' => 'avatar.jpg']);
        $directory = Messenger::getAvatarStorage('directory').'/user/'.$this->tippin->getKey();

        UploadedFile::fake()->image('avatar.jpg')->storeAs($directory, 'avatar.jpg', [
            'disk' => 'public',
        ]);

        $this->getJson(route('assets.messenger.provider.avatar.render', [
            'alias' => 'user',
            'id' => $this->tippin->getKey(),
            'size' => 'lg',
            'image' => 'avatar.jpg',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-disposition', 'inline; filename=avatar.jpg')
            ->assertHeader('content-type', 'image/jpeg')
            ->assertHeader('content-length', 695);
    }

    /** @test */
    public function it_doesnt_render_provider_avatar_if_filename_doesnt_match()
    {
        $this->tippin->update(['picture' => 'avatar.jpg']);
        $directory = Messenger::getAvatarStorage('directory').'/user/'.$this->tippin->getKey();

        UploadedFile::fake()->image('avatar.jpg')->storeAs($directory, 'avatar.jpg', [
            'disk' => 'public',
        ]);

        $this->getJson(route('assets.messenger.provider.avatar.render', [
            'alias' => 'user',
            'id' => $this->tippin->getKey(),
            'size' => 'lg',
            'image' => 'unknown.jpg',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-type', 'image/png')
            ->assertHeaderMissing('content-disposition')
            ->assertHeader('content-length', 95);
    }

    /** @test */
    public function it_renders_default_provider_avatar()
    {
        $this->getJson(route('assets.messenger.provider.avatar.render', [
            'alias' => 'user',
            'id' => $this->tippin->getKey(),
            'size' => 'lg',
            'image' => 'default.png',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-type', 'image/png')
            ->assertHeaderMissing('content-disposition')
            ->assertHeader('content-length', 95);
    }

    /** @test */
    public function it_renders_group_thread_avatar()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create(['image' => 'avatar.jpg']);
        UploadedFile::fake()->image('avatar.jpg')->storeAs($thread->getAvatarDirectory(), 'avatar.jpg', [
            'disk' => 'messenger',
        ]);

        $this->getJson(route('assets.messenger.threads.avatar.render', [
            'thread' => $thread->id,
            'size' => 'lg',
            'image' => 'avatar.jpg',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-disposition', 'inline; filename=avatar.jpg')
            ->assertHeader('content-type', 'image/jpeg')
            ->assertHeader('content-length', 695);
    }

    /** @test */
    public function it_doesnt_render_group_thread_avatar_if_filename_doesnt_match()
    {
        $thread = Thread::factory()->group()->create(['image' => 'avatar.jpg']);
        UploadedFile::fake()->image('avatar.jpg')->storeAs($thread->getAvatarDirectory(), 'avatar.jpg', [
            'disk' => 'messenger',
        ]);

        $this->getJson(route('assets.messenger.threads.avatar.render', [
            'thread' => $thread->id,
            'size' => 'lg',
            'image' => 'unknown.jpg',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-type', 'image/png')
            ->assertHeaderMissing('content-disposition')
            ->assertHeader('content-length', 95);
    }

    /** @test */
    public function it_renders_default_group_thread_avatar()
    {
        $thread = Thread::factory()->group()->create(['image' => null]);

        $this->getJson(route('assets.messenger.threads.avatar.render', [
            'thread' => $thread->id,
            'size' => 'lg',
            'image' => 'default.png',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-type', 'image/png')
            ->assertHeaderMissing('content-disposition')
            ->assertHeader('content-length', 95);
    }

    /** @test */
    public function it_renders_bot_avatar()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create(['avatar' => 'avatar.jpg']);
        UploadedFile::fake()->image('avatar.jpg')->storeAs($bot->getAvatarDirectory(), 'avatar.jpg', [
            'disk' => 'messenger',
        ]);

        $this->getJson(route('assets.messenger.threads.bots.avatar.render', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'size' => 'lg',
            'image' => 'avatar.jpg',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-disposition', 'inline; filename=avatar.jpg')
            ->assertHeader('content-type', 'image/jpeg')
            ->assertHeader('content-length', 695);
    }

    /** @test */
    public function it_doesnt_render_bot_avatar_if_filename_doesnt_match()
    {
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create(['avatar' => 'avatar.jpg']);
        UploadedFile::fake()->image('avatar.jpg')->storeAs($bot->getAvatarDirectory(), 'avatar.jpg', [
            'disk' => 'messenger',
        ]);

        $this->getJson(route('assets.messenger.threads.bots.avatar.render', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'size' => 'lg',
            'image' => 'unknown.jpg',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-type', 'image/png')
            ->assertHeaderMissing('content-disposition')
            ->assertHeader('content-length', 95);
    }

    /** @test */
    public function it_renders_default_bot_avatar()
    {
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();

        $this->getJson(route('assets.messenger.threads.bots.avatar.render', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'size' => 'lg',
            'image' => 'default.png',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-type', 'image/png')
            ->assertHeaderMissing('content-disposition')
            ->assertHeader('content-length', 95);
    }

    /** @test */
    public function it_renders_message_image()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->image()->create(['body' => 'foo.jpg']);
        UploadedFile::fake()->image('foo.jpg')->storeAs($thread->getImagesDirectory(), 'foo.jpg', [
            'disk' => 'messenger',
        ]);

        $this->getJson(route('assets.messenger.threads.gallery.render', [
            'thread' => $thread->id,
            'message' => $message->id,
            'size' => 'lg',
            'image' => 'foo.jpg',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-disposition', 'inline; filename=foo.jpg')
            ->assertHeader('content-type', 'image/jpeg')
            ->assertHeader('content-length', 695);
    }

    /** @test */
    public function it_doesnt_render_message_image_if_filename_doesnt_match()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->image()->create(['body' => 'foo.jpg']);
        UploadedFile::fake()->image('foo.jpg')->storeAs($thread->getImagesDirectory(), 'foo.jpg', [
            'disk' => 'messenger',
        ]);

        $this->getJson(route('assets.messenger.threads.gallery.render', [
            'thread' => $thread->id,
            'message' => $message->id,
            'size' => 'lg',
            'image' => 'unknown.jpg',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-type', 'image/png')
            ->assertHeaderMissing('content-disposition')
            ->assertHeader('content-length', 95);
    }

    /** @test */
    public function it_renders_not_found_message_image()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->image()->create(['body' => 'foo.jpg']);

        $this->getJson(route('assets.messenger.threads.gallery.render', [
            'thread' => $thread->id,
            'message' => $message->id,
            'size' => 'lg',
            'image' => 'foo.jpg',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-type', 'image/png')
            ->assertHeaderMissing('content-disposition')
            ->assertHeader('content-length', 95);
    }

    /** @test */
    public function it_downloads_message_document()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->document()->create(['body' => 'foo.pdf']);
        UploadedFile::fake()
            ->create('foo.pdf', 500, 'application/pdf')
            ->storeAs($thread->getDocumentsDirectory(), 'foo.pdf', [
                'disk' => 'messenger',
            ]);

        $this->getJson(route('assets.messenger.threads.files.download', [
            'thread' => $thread->id,
            'message' => $message->id,
            'file' => 'foo.pdf',
        ]))
            ->assertSuccessful()
            ->assertDownload()
            ->assertHeader('content-disposition', 'attachment; filename=foo.pdf');
    }

    /** @test */
    public function it_doesnt_download_message_document_if_filename_doesnt_match()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->document()->create(['body' => 'foo.pdf']);
        UploadedFile::fake()
            ->create('foo.pdf', 500, 'application/pdf')
            ->storeAs($thread->getDocumentsDirectory(), 'foo.pdf', [
                'disk' => 'messenger',
            ]);

        $this->getJson(route('assets.messenger.threads.files.download', [
            'thread' => $thread->id,
            'message' => $message->id,
            'file' => 'bar.pdf',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function it_doesnt_download_message_document_if_file_not_found()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->document()->create(['body' => 'foo.pdf']);

        $this->getJson(route('assets.messenger.threads.files.download', [
            'thread' => $thread->id,
            'message' => $message->id,
            'file' => 'foo.pdf',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function it_downloads_message_audio()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->audio()->create(['body' => 'foo.mp3']);
        UploadedFile::fake()
            ->create('foo.mp3', 500, 'audio/mpeg')
            ->storeAs($thread->getAudioDirectory(), 'foo.mp3', [
                'disk' => 'messenger',
            ]);

        $this->getJson(route('assets.messenger.threads.audio.download', [
            'thread' => $thread->id,
            'message' => $message->id,
            'audio' => 'foo.mp3',
        ]))
            ->assertSuccessful()
            ->assertDownload()
            ->assertHeader('content-disposition', 'attachment; filename=foo.mp3');
    }

    /** @test */
    public function it_streams_message_audio()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->audio()->create(['body' => 'foo.mp3']);
        UploadedFile::fake()
            ->create('foo.mp3', 500, 'audio/mpeg')
            ->storeAs($thread->getAudioDirectory(), 'foo.mp3', [
                'disk' => 'messenger',
            ]);

        $this->getJson(route('assets.messenger.threads.audio.download', [
            'thread' => $thread->id,
            'message' => $message->id,
            'audio' => 'foo.mp3',
        ]).'?stream=true')
            ->assertSuccessful()
            ->assertHeader('content-type', 'application/x-empty');
    }

    /** @test */
    public function it_doesnt_download_message_audio_if_filename_doesnt_match()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->audio()->create(['body' => 'foo.mp3']);
        UploadedFile::fake()
            ->create('foo.mp3', 500, 'audio/mpeg')
            ->storeAs($thread->getAudioDirectory(), 'foo.mp3', [
                'disk' => 'messenger',
            ]);

        $this->getJson(route('assets.messenger.threads.audio.download', [
            'thread' => $thread->id,
            'message' => $message->id,
            'audio' => 'bar.mp3',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function it_doesnt_download_message_audio_if_file_not_found()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->audio()->create(['body' => 'foo.mp3']);

        $this->getJson(route('assets.messenger.threads.audio.download', [
            'thread' => $thread->id,
            'message' => $message->id,
            'audio' => 'foo.mp3',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function it_downloads_message_video()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->video()->create(['body' => 'foo.mov']);
        UploadedFile::fake()
            ->create('foo.mov', 500, 'video/quicktime')
            ->storeAs($thread->getVideoDirectory(), 'foo.mov', [
                'disk' => 'messenger',
            ]);

        $this->getJson(route('assets.messenger.threads.videos.download', [
            'thread' => $thread->id,
            'message' => $message->id,
            'video' => 'foo.mov',
        ]))
            ->assertSuccessful()
            ->assertDownload()
            ->assertHeader('content-disposition', 'attachment; filename=foo.mov');
    }

    /** @test */
    public function it_streams_message_video()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->video()->create(['body' => 'foo.mov']);
        UploadedFile::fake()
            ->create('foo.mov', 500, 'video/quicktime')
            ->storeAs($thread->getVideoDirectory(), 'foo.mov', [
                'disk' => 'messenger',
            ]);

        $this->getJson(route('assets.messenger.threads.videos.download', [
            'thread' => $thread->id,
            'message' => $message->id,
            'video' => 'foo.mov',
        ]).'?stream=true')
            ->assertSuccessful()
            ->assertHeader('content-type', 'application/x-empty');
    }

    /** @test */
    public function it_doesnt_download_message_video_if_filename_doesnt_match()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->video()->create(['body' => 'foo.mov']);
        UploadedFile::fake()
            ->create('foo.mov', 500, 'video/quicktime')
            ->storeAs($thread->getVideoDirectory(), 'foo.mov', [
                'disk' => 'messenger',
            ]);

        $this->getJson(route('assets.messenger.threads.videos.download', [
            'thread' => $thread->id,
            'message' => $message->id,
            'video' => 'bar.mov',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function it_doesnt_download_message_video_if_file_not_found()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->video()->create(['body' => 'foo.mov']);

        $this->getJson(route('assets.messenger.threads.videos.download', [
            'thread' => $thread->id,
            'message' => $message->id,
            'video' => 'foo.mov',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function it_renders_group_thread_avatar_through_invite()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create(['image' => 'avatar.jpg']);
        $invite = Invite::factory()->for($thread)->owner($this->tippin)->create();
        UploadedFile::fake()->image('avatar.jpg')->storeAs($thread->getAvatarDirectory(), 'avatar.jpg', [
            'disk' => 'messenger',
        ]);

        $this->getJson(route('assets.messenger.invites.avatar.render', [
            'invite' => $invite->code,
            'size' => 'lg',
            'image' => 'avatar.jpg',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-disposition', 'inline; filename=avatar.jpg')
            ->assertHeader('content-type', 'image/jpeg')
            ->assertHeader('content-length', 695);
    }

    /** @test */
    public function it_is_forbidden_to_view_group_thread_avatar_if_invite_invalid()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create(['image' => 'avatar.jpg']);
        $invite = Invite::factory()->for($thread)->owner($this->tippin)->invalid()->create();
        UploadedFile::fake()->image('avatar.jpg')->storeAs($thread->getAvatarDirectory(), 'avatar.jpg', [
            'disk' => 'messenger',
        ]);

        $this->getJson(route('assets.messenger.invites.avatar.render', [
            'invite' => $invite->code,
            'size' => 'lg',
            'image' => 'avatar.jpg',
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function it_doesnt_render_group_thread_avatar_if_filename_doesnt_match_through_invite()
    {
        $thread = Thread::factory()->group()->create(['image' => 'avatar.jpg']);
        $invite = Invite::factory()->for($thread)->owner($this->tippin)->create();
        UploadedFile::fake()->image('avatar.jpg')->storeAs($thread->getAvatarDirectory(), 'avatar.jpg', [
            'disk' => 'messenger',
        ]);

        $this->getJson(route('assets.messenger.invites.avatar.render', [
            'invite' => $invite->code,
            'size' => 'lg',
            'image' => 'unknown.jpg',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-type', 'image/png')
            ->assertHeaderMissing('content-disposition')
            ->assertHeader('content-length', 95);
    }

    /** @test */
    public function it_renders_default_group_thread_avatar_through_invite()
    {
        $thread = Thread::factory()->group()->create(['image' => null]);
        $invite = Invite::factory()->for($thread)->owner($this->tippin)->create();

        $this->getJson(route('assets.messenger.invites.avatar.render', [
            'invite' => $invite->code,
            'size' => 'lg',
            'image' => 'default.png',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-type', 'image/png')
            ->assertHeaderMissing('content-disposition')
            ->assertHeader('content-length', 95);
    }

    /** @test */
    public function it_renders_packaged_bot_avatar()
    {
        $this->logCurrentRequest();
        SillyBotPackage::$avatar = __DIR__.'/../Fixtures/avatar.jpg';
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);

        $this->getJson(route('assets.messenger.bot-package.avatar.render', [
            'size' => 'lg',
            'alias' => 'silly_package',
            'image' => 'avatar.jpg',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-type', 'image/jpeg')
            ->assertHeaderMissing('content-disposition')
            ->assertHeader('content-length', 7503);
    }

    /** @test */
    public function it_renders_default_packaged_bot_avatar()
    {
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $this->getJson(route('assets.messenger.bot-package.avatar.render', [
            'size' => 'lg',
            'alias' => 'fun_package',
            'image' => 'avatar.png',
        ]))
            ->assertSuccessful()
            ->assertHeader('content-type', 'image/png')
            ->assertHeaderMissing('content-disposition')
            ->assertHeader('content-length', 95);
    }
}
