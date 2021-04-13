<?php

namespace RTippin\Messenger\Tests\Messenger;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Tests\FeatureTestCase;

class RoutesDisabledTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $config = $app->get('config');
        $config->set('messenger.routing.web.enabled', false);
        $config->set('messenger.routing.provider_avatar.enabled', false);
        $config->set('messenger.routing.channels.enabled', false);
    }

    /** @test */
    public function routes_are_disabled()
    {
        $this->assertFalse(Messenger::isWebRoutesEnabled());
        $this->assertFalse(Messenger::isProviderAvatarRoutesEnabled());
        $this->assertFalse(Messenger::isChannelRoutesEnabled());
    }

    /** @test */
    public function invite_web_route_null()
    {
        $group = $this->createGroupThread($this->tippin);
        $invite = Invite::factory()->for($group)->owner($this->tippin)->create();

        $this->assertNull($invite->getInvitationRoute());
    }

    /** @test */
    public function provider_avatar_routes_null()
    {
        $this->assertNull($this->tippin->getAvatarRoute());
        $this->assertNull($this->tippin->getAvatarRoute('sm', true));
    }

    /** @test */
    public function group_thread_web_route_avatar_values_null()
    {
        $group = $this->createGroupThread($this->tippin);
        Messenger::setProvider($this->tippin);
        $groupAvatar = [
            'sm' => null,
            'md' => null,
            'lg' => null,
        ];

        $this->assertSame($groupAvatar, $group->threadAvatar());
    }

    /** @test */
    public function image_message_web_route_null()
    {
        $group = $this->createGroupThread($this->tippin);
        $message = $group->messages()->create([
            'body' => 'test.png',
            'type' => 1,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
        ]);

        $this->assertNull($message->getImageViewRoute());
    }

    /** @test */
    public function document_message_web_route_null()
    {
        $group = $this->createGroupThread($this->tippin);
        $message = $group->messages()->create([
            'body' => 'test.pdf',
            'type' => 2,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
        ]);

        $this->assertNull($message->getDocumentDownloadRoute());
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
