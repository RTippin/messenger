<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

class MessengerTest extends FeatureTestCase
{
    /** @test */
    public function test_guest_was_denied()
    {
        $this->get(route('api.messenger.info'))
            ->assertUnauthorized();
    }

    /** @test */
    public function test_messenger_info_was_successful()
    {
        $this->actingAs(UserModel::first());

        $this->get(route('api.messenger.info'))
            ->assertSuccessful()
            ->assertJson([
                'siteName' => 'Messenger-Testbench',
                'messageImageUpload' => true,
                'calling' => false,
                'threadsIndexCount' => 100,
            ]);
    }

    /** @test */
    public function test_messenger_info_changes_when_set_dynamically()
    {
        $this->actingAs(UserModel::first());

        $this->get(route('api.messenger.info'))
            ->assertSuccessful()
            ->assertJson([
                'siteName' => 'Messenger-Testbench',
                'messageImageUpload' => true,
                'calling' => false,
                'threadsIndexCount' => 100,
            ]);

        Messenger::setCalling(true);
        Messenger::setMessageImageUpload(false);
        Messenger::setThreadsIndexCount(50);

        $this->get(route('api.messenger.info'))
            ->assertSuccessful()
            ->assertJson([
                'siteName' => 'Messenger-Testbench',
                'messageImageUpload' => false,
                'calling' => true,
                'threadsIndexCount' => 50,
            ]);
    }

    /** @test */
    public function test_messenger_created_when_called_from_user_without_messenger()
    {
        $user = UserModel::first();

        $this->assertDatabaseMissing('messengers', [
            'owner_id' => $user->getKey(),
        ]);

        $this->actingAs($user);

        $this->get(route('api.messenger.settings'))
            ->assertSuccessful()
            ->assertJson([
                'owner_id' => $user->getKey(),
                'dark_mode' => true,
            ]);

        $this->assertDatabaseHas('messengers', [
            'owner_id' => $user->getKey(),
        ]);
    }
}
