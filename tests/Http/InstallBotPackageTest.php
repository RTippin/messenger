<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Tests\Fixtures\FunBotPackage;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotPackage;
use RTippin\Messenger\Tests\HttpTestCase;

class InstallBotPackageTest extends HttpTestCase
{
    /** @test */
    public function forbidden_to_install_packaged_bot_if_bots_disabled()
    {
        Messenger::setBots(false);
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.packages.store', [
            'thread' => $thread->id,
        ]), [
            'alias' => 'fun_package',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_install_packaged_bot()
    {
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.bots.packages.store', [
            'thread' => $thread->id,
        ]), [
            'alias' => 'fun_package',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_install_packaged_bot_if_authorization_fails()
    {
        $this->logCurrentRequest();
        SillyBotPackage::$authorized = false;
        MessengerBots::registerPackagedBots([SillyBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.packages.store', [
            'thread' => $thread->id,
        ]), [
            'alias' => 'silly_package',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_install_packaged_bot()
    {
        $this->logCurrentRequest();
        SillyBotHandler::$authorized = true;
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.packages.store', [
            'thread' => $thread->id,
        ]), [
            'alias' => 'fun_package',
        ])
            ->assertSuccessful()
            ->assertJson([
                'name' => 'Fun Package',
            ]);
    }

    /** @test */
    public function participant_with_permission_can_install_packaged_bot()
    {
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        Participant::factory()->for($thread)->owner($this->doe)->create(['manage_bots' => true]);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.bots.packages.store', [
            'thread' => $thread->id,
        ]), [
            'alias' => 'fun_package',
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     *
     * @dataProvider aliasFailsValidation
     *
     * @param  $alias
     */
    public function install_packaged_bot_fails_alias_validation($alias)
    {
        $this->logCurrentRequest();
        MessengerBots::registerPackagedBots([FunBotPackage::class]);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.packages.store', [
            'thread' => $thread->id,
        ]), [
            'alias' => $alias,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['alias']);
    }

    public static function aliasFailsValidation(): array
    {
        return [
            'Alias is required' => [null],
            'Alias cannot be boolean' => [true],
            'Alias cannot be an integer' => [30],
            'Alias cannot be an array' => [[1, 2]],
            'Alias must be a valid registered alias' => ['unknown'],
        ];
    }
}
