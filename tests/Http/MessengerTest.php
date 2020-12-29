<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\stubs\UserModel;

class MessengerTest extends FeatureTestCase
{
    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.info'))
            ->assertUnauthorized();
    }

    /** @test */
    public function messenger_info_was_successful()
    {
        $this->actingAs(UserModel::find(1));

        $this->getJson(route('api.messenger.info'))
            ->assertSuccessful()
            ->assertJson([
                'siteName' => 'Messenger-Testbench',
                'messageImageUpload' => true,
                'calling' => false,
                'threadsIndexCount' => 100,
            ]);
    }

    /** @test */
    public function messenger_info_changes_when_set_dynamically()
    {
        $this->actingAs(UserModel::find(1));

        $this->getJson(route('api.messenger.info'))
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

        $this->getJson(route('api.messenger.info'))
            ->assertSuccessful()
            ->assertJson([
                'siteName' => 'Messenger-Testbench',
                'messageImageUpload' => false,
                'calling' => true,
                'threadsIndexCount' => 50,
            ]);
    }
}
