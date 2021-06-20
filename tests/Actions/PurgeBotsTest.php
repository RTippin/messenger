<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Bots\PurgeBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeBotsTest extends FeatureTestCase
{
    /** @test */
    public function it_removes_bots_and_related_actions()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();
        Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->create();

        app(PurgeBots::class)->execute(Bot::all());

        $this->assertDatabaseCount('bots', 0);
        $this->assertDatabaseCount('bot_actions', 0);
    }

    /** @test */
    public function it_removes_bot_directory_from_disk()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();
        UploadedFile::fake()
            ->image('avatar.jpg')
            ->storeAs($bot->getAvatarDirectory(), 'avatar.jpg', [
                'disk' => 'messenger',
            ]);

        app(PurgeBots::class)->execute(Bot::all());

        Storage::disk('messenger')->assertMissing($bot->getStorageDirectory());
    }
}
