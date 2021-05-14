<?php

namespace RTippin\Messenger\Tests\JsonResources;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Http\Resources\MessengerResource;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessengerResourceTest extends FeatureTestCase
{
    /** @test */
    public function it_transforms_messenger()
    {
        Messenger::setProvider($this->tippin);

        $resource = (new MessengerResource(Messenger::getProviderMessenger()))->resolve();
        $messenger = Messenger::getProviderMessenger()->toArray();

        $this->assertEquals($this->tippin->getKey(), $resource['owner_id']);
        $this->assertSame($this->tippin->getMorphClass(), $resource['owner_type']);
        $this->assertSame($messenger['created_at'], $resource['created_at']);
        $this->assertSame($messenger['updated_at'], $resource['updated_at']);
        $this->assertIsArray($resource['owner']);
        $this->assertSame($messenger['id'], $resource['id']);
        $this->assertTrue($resource['message_popups']);
        $this->assertTrue($resource['message_sound']);
        $this->assertTrue($resource['call_ringtone_sound']);
        $this->assertTrue($resource['notify_sound']);
        $this->assertTrue($resource['dark_mode']);
        $this->assertSame(1, $resource['online_status']);
    }
}
