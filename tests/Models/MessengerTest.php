<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger as MessengerFacade;
use RTippin\Messenger\Models\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\UserModel;

class MessengerTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $messenger = Messenger::factory()->owner(
            UserModel::factory()->create()
        )->create();

        $this->assertDatabaseHas('messengers', [
            'id' => $messenger->id,
        ]);
        $this->assertInstanceOf(Messenger::class, $messenger);
    }

    /** @test */
    public function it_cast_attributes()
    {
        $messenger = Messenger::factory()->owner(
            UserModel::factory()->create()
        )->create();

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
        $user = UserModel::factory()->create();
        $messenger = Messenger::factory()->owner($user)->create();

        $this->assertSame($user->getKey(), $messenger->owner->getKey());
        $this->assertInstanceOf(MessengerProvider::class, $messenger->owner);
    }

    /** @test */
    public function it_is_owned_by_current_provider()
    {
        $user = UserModel::factory()->create();
        $messenger = Messenger::factory()->owner($user)->create();
        MessengerFacade::setProvider($user);

        $this->assertTrue($messenger->isOwnedByCurrentProvider());
    }

    /** @test */
    public function it_is_not_owned_by_current_provider()
    {
        MessengerFacade::setProvider($this->doe);
        $messenger = Messenger::factory()->owner(
            UserModel::factory()->create()
        )->create();

        $this->assertFalse($messenger->isOwnedByCurrentProvider());
    }

    /** @test */
    public function it_has_private_owner_channel()
    {
        $user = UserModel::factory()->create();
        $messenger = Messenger::factory()->owner($user)->create();

        $this->assertSame('user.'.$user->getKey(), $messenger->getOwnerPrivateChannel());
    }
}
