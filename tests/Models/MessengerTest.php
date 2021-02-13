<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger as MessengerFacade;
use RTippin\Messenger\Models\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessengerTest extends FeatureTestCase
{
    private MessengerProvider $tippin;

    private Messenger $messengerModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->messengerModel = MessengerFacade::getProviderMessenger($this->tippin);
    }

    /** @test */
    public function messenger_exists()
    {
        $this->assertDatabaseHas('messengers', [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
        ]);
        $this->assertInstanceOf(Messenger::class, $this->messengerModel);
    }

    /** @test */
    public function messenger_attributes_casted()
    {
        $this->assertInstanceOf(Carbon::class, $this->messengerModel->created_at);
        $this->assertInstanceOf(Carbon::class, $this->messengerModel->updated_at);
        $this->assertTrue($this->messengerModel->message_popups);
        $this->assertTrue($this->messengerModel->message_sound);
        $this->assertTrue($this->messengerModel->call_ringtone_sound);
        $this->assertTrue($this->messengerModel->notify_sound);
        $this->assertTrue($this->messengerModel->dark_mode);
        $this->assertSame(1, $this->messengerModel->online_status);
    }

    /** @test */
    public function messenger_has_relation()
    {
        $this->assertSame($this->tippin->getKey(), $this->messengerModel->owner->getKey());
        $this->assertInstanceOf(MessengerProvider::class, $this->messengerModel->owner);
    }
}
