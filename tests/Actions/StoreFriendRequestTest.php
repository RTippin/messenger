<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Friends\StoreFriendRequest;
use RTippin\Messenger\Broadcasting\FriendRequestBroadcast;
use RTippin\Messenger\Events\FriendRequestEvent;
use RTippin\Messenger\Exceptions\FriendException;
use RTippin\Messenger\Exceptions\ProviderNotFoundException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\UserModel;

class StoreFriendRequestTest extends FeatureTestCase
{
    use BroadcastLogger;

    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_stores_sent_friend()
    {
        app(StoreFriendRequest::class)->execute([
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ]);

        $this->assertDatabaseHas('pending_friends', [
            'sender_id' => $this->tippin->getKey(),
            'sender_type' => $this->tippin->getMorphClass(),
            'recipient_id' => $this->doe->getKey(),
            'recipient_type' => $this->doe->getMorphClass(),
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        app(StoreFriendRequest::class)->execute([
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ]);

        Event::assertDispatched(function (FriendRequestBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->tippin->getKey(), $event->broadcastWith()['sender_id']);
            $this->assertSame('Richard Tippin', $event->broadcastWith()['sender']['name']);

            return true;
        });
        Event::assertDispatched(function (FriendRequestEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->friend->sender_id);
            $this->assertSame($this->doe->getKey(), $event->friend->recipient_id);

            return true;
        });
        $this->logBroadcast(FriendRequestBroadcast::class);
    }

    /** @test */
    public function it_throws_exception_if_provider_not_found()
    {
        $this->expectException(ProviderNotFoundException::class);
        $this->expectExceptionMessage('We were unable to locate the recipient you requested.');

        app(StoreFriendRequest::class)->execute([
            'recipient_id' => 404,
            'recipient_alias' => 'user',
        ]);
    }

    /** @test */
    public function it_throws_exception_if_adding_yourself()
    {
        $this->expectException(FriendException::class);
        $this->expectExceptionMessage('Cannot friend yourself.');

        app(StoreFriendRequest::class)->execute([
            'recipient_id' => $this->tippin->getKey(),
            'recipient_alias' => 'user',
        ]);
    }

    /** @test */
    public function it_throws_exception_if_already_friends()
    {
        $this->createFriends($this->tippin, $this->doe);

        $this->expectException(FriendException::class);
        $this->expectExceptionMessage('You are already friends, or have a pending request with John Doe.');

        app(StoreFriendRequest::class)->execute([
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ]);
    }

    /** @test */
    public function it_throws_exception_if_sent_friend_exist()
    {
        SentFriend::factory()->providers($this->tippin, $this->doe)->create();

        $this->expectException(FriendException::class);
        $this->expectExceptionMessage('You are already friends, or have a pending request with John Doe.');

        app(StoreFriendRequest::class)->execute([
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ]);
    }

    /** @test */
    public function it_throws_exception_if_pending_friend_exist()
    {
        PendingFriend::factory()->providers($this->doe, $this->tippin)->create();

        $this->expectException(FriendException::class);
        $this->expectExceptionMessage('You are already friends, or have a pending request with John Doe.');

        app(StoreFriendRequest::class)->execute([
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ]);
    }

    /** @test */
    public function it_throws_exception_if_disabled_in_provider_interactions()
    {
        UserModel::$cantFriend = [CompanyModel::class];
        Messenger::registerProviders([UserModel::class, CompanyModel::class]);
        Messenger::setProvider($this->tippin);

        $this->expectException(FriendException::class);
        $this->expectExceptionMessage('Not authorized to add friend.');

        app(StoreFriendRequest::class)->execute([
            'recipient_id' => $this->developers->getKey(),
            'recipient_alias' => 'company',
        ]);
    }
}
