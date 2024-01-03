<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class BotAvatarTest extends HttpTestCase
{
    /** @test */
    public function forbidden_to_upload_avatar_when_bots_disabled_in_config()
    {
        $this->logCurrentRequest();
        Messenger::setBots(false);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.avatar.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_upload_avatar_when_bot_avatars_disabled_in_config()
    {
        Messenger::setBotAvatars(false);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.avatar.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_upload_avatar_when_disabled_in_thread()
    {
        $thread = Thread::factory()->group()->create(['chat_bots' => false]);
        Participant::factory()->for($thread)->admin()->owner($this->tippin)->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.avatar.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertForbidden();
    }

    /** @test */
    public function participant_without_permission_forbidden_to_upload_avatar()
    {
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.bots.avatar.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_upload_avatar()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.avatar.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function participant_with_permission_can_upload_avatar()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create(['manage_bots' => true]);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.bots.avatar.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'image' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_remove_avatar()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);
        UploadedFile::fake()->image('avatar.jpg')->storeAs($bot->getAvatarDirectory(), 'avatar.jpg', [
            'disk' => 'messenger',
        ]);
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.bots.avatar.destroy', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function participant_with_permission_can_remove_avatar()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create(['manage_bots' => true]);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.bots.avatar.destroy', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function participant_without_permission_forbidden_to_remove_avatar()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.bots.avatar.destroy', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_remove_avatar_when_bots_disabled_in_config()
    {
        Messenger::setBots(false);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.bots.avatar.destroy', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_remove_avatar_when_disabled_in_thread()
    {
        $thread = Thread::factory()->group()->create(['chat_bots' => false]);
        Participant::factory()->for($thread)->admin()->owner($this->tippin)->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.bots.avatar.destroy', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
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
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.avatar.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
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
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.avatar.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
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
            'Avatar can be webp' => [UploadedFile::fake()->create('image.svg', 500, 'image/webp')],
            'Avatar can be 5120 kb max limit' => [UploadedFile::fake()->create('image.jpg', 5120, 'image/jpeg')],
        ];
    }
}
