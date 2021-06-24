<?php

namespace RTippin\Messenger\Tests\Messenger;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class ChannelRoutesDisabledTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $config = $app->get('config');
        $config->set('messenger.routing.channels.enabled', false);
    }

    /** @test */
    public function routes_are_disabled()
    {
        $this->assertFalse(Messenger::isChannelRoutesEnabled());
    }

    /** @test */
    public function provider_channel_not_found()
    {
        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-messenger.user.1',
        ])
            ->assertNotFound();
    }

    /** @test */
    public function thread_channel_not_found()
    {
        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'presence-messenger.thread.1',
        ])
            ->assertNotFound();
    }

    /** @test */
    public function call_channel_not_found()
    {
        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'presence-messenger.call.1.thread.1',
        ])
            ->assertNotFound();
    }
}
