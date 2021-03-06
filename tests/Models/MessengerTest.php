<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger as MessengerFacade;
use RTippin\Messenger\Models\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessengerTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $messenger = MessengerFacade::getProviderMessenger($this->tippin);

        $this->assertDatabaseHas('messengers', [
            'id' => $messenger->id,
        ]);
        $this->assertInstanceOf(Messenger::class, $messenger);
    }

    /** @test */
    public function it_cast_attributes()
    {
        $messenger = MessengerFacade::getProviderMessenger($this->tippin);

        $this->assertInstanceOf(Carbon::class, $messenger->created_at);
        $this->assertInstanceOf(Carbon::class, $messenger->updated_at);
        $this->assertTrue($messenger->message_popups);
        $this->assertTrue($messenger->message_sound);
        $this->assertTrue($messenger->call_ringtone_sound);
        $this->assertTrue($messenger->notify_sound);
        $this->assertTrue($messenger->dark_mode);
        $this->assertSame(1, $messenger->online_status);
    }

    /** @test */
    public function it_has_relation()
    {
        $messenger = MessengerFacade::getProviderMessenger($this->tippin);

        $this->assertSame($this->tippin->getKey(), $messenger->owner->getKey());
        $this->assertInstanceOf(MessengerProvider::class, $messenger->owner);
    }
}
