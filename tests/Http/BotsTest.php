<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class BotsTest extends HttpTestCase
{
    /** @test */
    public function admin_can_view_bots()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        Bot::factory()->for($thread)->owner($this->tippin)->count(2)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2);
    }

    /** @test */
    public function forbidden_to_view_bots_when_disabled_in_config()
    {
        $this->logCurrentRequest();
        Messenger::setBots(false);
        $thread = $this->createGroupThread($this->tippin);
        Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.index', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_view_bots_when_disabled_in_thread()
    {
        $thread = Thread::factory()->group()->create(['chat_bots' => false]);
        Participant::factory()->for($thread)->admin()->owner($this->tippin)->create();
        Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.index', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_can_view_bots()
    {
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        Bot::factory()->for($thread)->owner($this->tippin)->count(2)->create();
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.bots.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2);
    }

    /** @test */
    public function admin_can_add_bot()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.store', [
            'thread' => $thread->id,
        ]), [
            'name' => 'Test Bot',
            'enabled' => true,
            'hide_actions' => false,
            'cooldown' => 0,
        ])
            ->assertSuccessful()
            ->assertJson([
                'name' => 'Test Bot',
                'owner_id' => $this->tippin->getKey(),
                'owner_type' => $this->tippin->getMorphClass(),
            ]);
    }

    /** @test */
    public function participant_with_permission_can_add_bot()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create(['manage_bots' => true]);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.bots.store', [
            'thread' => $thread->id,
        ]), [
            'name' => 'Test Bot',
            'enabled' => true,
            'hide_actions' => false,
            'cooldown' => 0,
        ])
            ->assertSuccessful()
            ->assertJson([
                'name' => 'Test Bot',
                'owner_id' => $this->doe->getKey(),
                'owner_type' => $this->doe->getMorphClass(),
            ]);
    }

    /** @test */
    public function forbidden_to_add_bot_when_disabled_in_config()
    {
        $this->logCurrentRequest();
        Messenger::setBots(false);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.store', [
            'thread' => $thread->id,
        ]), [
            'name' => 'Test Bot',
            'enabled' => true,
            'hide_actions' => false,
            'cooldown' => 0,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_add_bot_when_disabled_in_thread()
    {
        $thread = Thread::factory()->group()->create(['chat_bots' => false]);
        Participant::factory()->for($thread)->admin()->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.store', [
            'thread' => $thread->id,
        ]), [
            'name' => 'Test Bot',
            'enabled' => true,
            'hide_actions' => false,
            'cooldown' => 0,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function participant_without_permission_forbidden_to_add_bot()
    {
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.bots.store', [
            'thread' => $thread->id,
        ]), [
            'name' => 'Test Bot',
            'enabled' => true,
            'hide_actions' => false,
            'cooldown' => 0,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_view_bot()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create(['name' => 'Test Bot']);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.show', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'name' => 'Test Bot',
                'owner_id' => $this->tippin->getKey(),
                'owner_type' => $this->tippin->getMorphClass(),
            ]);
    }

    /** @test */
    public function forbidden_to_view_bot_when_disabled_in_config()
    {
        $this->logCurrentRequest();
        Messenger::setBots(false);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.show', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_view_bot_when_disabled_in_thread_settings()
    {
        $thread = Thread::factory()->group()->create(['chat_bots' => false]);
        Participant::factory()->for($thread)->admin()->owner($this->tippin)->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.show', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_can_view_bot()
    {
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create(['name' => 'Test Bot']);
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.bots.show', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'name' => 'Test Bot',
                'owner_id' => $this->tippin->getKey(),
                'owner_type' => $this->tippin->getMorphClass(),
            ]);
    }

    /** @test */
    public function admin_can_remove_bot()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.bots.destroy', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function participant_with_permission_can_remove_bot()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create(['manage_bots' => true]);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.bots.destroy', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function participant_without_permission_forbidden_to_remove_bot()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.bots.destroy', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_update_bot()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.bots.update', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'name' => 'Renamed',
            'enabled' => false,
            'hide_actions' => true,
            'cooldown' => 99,
        ])
            ->assertSuccessful()
            ->assertJson([
                'name' => 'Renamed',
                'enabled' => false,
                'hide_actions' => true,
                'cooldown' => 99,
            ]);
    }

    /** @test */
    public function participant_with_permission_can_update_bot()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create(['manage_bots' => true]);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->putJson(route('api.messenger.threads.bots.update', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'name' => 'Renamed',
            'enabled' => false,
            'hide_actions' => false,
            'cooldown' => 99,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function participant_without_permission_forbidden_to_update_bot()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->putJson(route('api.messenger.threads.bots.update', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'name' => 'Renamed',
            'enabled' => false,
            'hide_actions' => false,
            'cooldown' => 99,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_update_bot_when_disabled_in_config()
    {
        Messenger::setBots(false);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.bots.update', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'name' => 'Renamed',
            'enabled' => false,
            'hide_actions' => false,
            'cooldown' => 99,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_update_bot_when_disabled_in_thread()
    {
        $thread = Thread::factory()->group()->create(['chat_bots' => false]);
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.bots.update', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'name' => 'Renamed',
            'enabled' => false,
            'hide_actions' => false,
            'cooldown' => 99,
        ])
            ->assertForbidden();
    }

    /**
     * @test
     *
     * @dataProvider botFailsValidation
     *
     * @param  $name
     * @param  $enabled
     * @param  $hide
     * @param  $cooldown
     * @param  $errors
     */
    public function store_bot_fails_validation($name, $enabled, $hide, $cooldown, $errors)
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.store', [
            'thread' => $thread->id,
        ]), [
            'name' => $name,
            'enabled' => $enabled,
            'hide_actions' => $hide,
            'cooldown' => $cooldown,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors($errors);
    }

    /**
     * @test
     *
     * @dataProvider botPassesValidation
     *
     * @param  $name
     * @param  $enabled
     * @param  $hide
     * @param  $cooldown
     */
    public function store_bot_passes_validation($name, $enabled, $hide, $cooldown)
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.store', [
            'thread' => $thread->id,
        ]), [
            'name' => $name,
            'enabled' => $enabled,
            'hide_actions' => $hide,
            'cooldown' => $cooldown,
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     *
     * @dataProvider botFailsValidation
     *
     * @param  $name
     * @param  $enabled
     * @param  $cooldown
     * @param  $errors
     */
    public function update_bot_fails_validation($name, $enabled, $hide, $cooldown, $errors)
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.bots.update', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'name' => $name,
            'enabled' => $enabled,
            'hide_actions' => $hide,
            'cooldown' => $cooldown,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors($errors);
    }

    /**
     * @test
     *
     * @dataProvider botPassesValidation
     *
     * @param  $name
     * @param  $enabled
     * @param  $hide
     * @param  $cooldown
     */
    public function update_bot_passes_validation($name, $enabled, $hide, $cooldown)
    {
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'name' => $name,
            'enabled' => $enabled,
            'hide_actions' => $hide,
            'cooldown' => $cooldown,
        ])
            ->assertSuccessful();
    }

    public static function botFailsValidation(): array
    {
        return [
            'All values required' => [null, null, null, null, ['name', 'enabled', 'hide_actions', 'cooldown']],
            'Name and cooldown cannot be boolean' => [true, false, false, false, ['name', 'cooldown']],
            'Name must be at least two characters' => ['T', false, false, 0, ['name']],
            'Name cannot be over 255 characters' => [str_repeat('X', 256), false, false, 0, ['name']],
            'Name cannot be a file' => [UploadedFile::fake()->image('picture.png'), false, false, 0, ['name']],
            'Cooldown cannot be negative' => ['Test', false, false, -1, ['cooldown']],
            'Cooldown cannot be over 900' => ['Test', false, false, 901, ['cooldown']],
        ];
    }

    public static function botPassesValidation(): array
    {
        return [
            'Bot name min 2 characters, false booleans, min 0 cooldown' => ['Te', false, false, 0],
            'Bot name max 255 characters' => [str_repeat('X', 255), false, false, 0],
            'True booleans, max 900 cooldown' => ['Test', true, true, 900],
            'Cooldown of 1' => ['Test More', true, true, 1],
        ];
    }
}
