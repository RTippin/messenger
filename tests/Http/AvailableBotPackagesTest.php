<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\Fixtures\FunBotPackage;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotPackage;
use RTippin\Messenger\Tests\HttpTestCase;

class AvailableBotPackagesTest extends HttpTestCase
{
    /** @test */
    public function it_displays_packages_filtering_installable_and_already_installed_unique_handlers()
    {
        $this->logCurrentRequest();
        SillyBotHandler::$authorized = true;
        MessengerBots::registerPackagedBots([
            FunBotPackage::class,
            SillyBotPackage::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )
            ->owner($this->tippin)
            ->handler(SillyBotHandler::class)
            ->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.packages.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                [
                    'alias' => 'fun_package',
                    'installs' => [
                        [
                            'alias' => 'broken_bot',
                        ],
                        [
                            'alias' => 'fun_bot',
                        ],
                    ],
                    'already_installed' => [
                        [
                            'alias' => 'silly_bot',
                        ],
                    ],
                ],
                [
                    'alias' => 'silly_package',
                    'installs' => [
                        [
                            'alias' => 'fun_bot',
                        ],
                    ],
                    'already_installed' => [
                        [
                            'alias' => 'silly_bot',
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function unauthorized_handlers_within_a_packaged_bot_are_omitted()
    {
        SillyBotHandler::$authorized = false;
        MessengerBots::registerPackagedBots([
            FunBotPackage::class,
            SillyBotPackage::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.packages.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                [
                    'alias' => 'fun_package',
                    'installs' => [
                        [
                            'alias' => 'broken_bot',
                        ],
                        [
                            'alias' => 'fun_bot',
                        ],
                    ],
                ],
                [
                    'alias' => 'silly_package',
                    'installs' => [
                        [
                            'alias' => 'fun_bot',
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function packages_failing_authorization_are_omitted()
    {
        SillyBotPackage::$authorized = false;
        MessengerBots::registerPackagedBots([
            FunBotPackage::class,
            SillyBotPackage::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.packages.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'alias' => 'fun_package',
                ],
            ]);
    }

    /** @test */
    public function participant_with_permission_can_view_available_packages()
    {
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create(['manage_bots' => true]);
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.bots.packages.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function participant_without_permission_forbidden_to_view_available_packages()
    {
        $this->logCurrentRequest();
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.bots.packages.index', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_view_available_handlers_when_disabled_in_config()
    {
        Messenger::setBots(false);
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.packages.index', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_view_available_handlers_when_disabled_in_thread_settings()
    {
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = Thread::factory()->group()->create(['chat_bots' => false]);
        Participant::factory()->for($thread)->admin()->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.packages.index', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }
}
