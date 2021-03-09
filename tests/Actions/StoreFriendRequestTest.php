<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Friends\StoreFriendRequest;
use RTippin\Messenger\Broadcasting\FriendRequestBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\FriendRequestEvent;
use RTippin\Messenger\Exceptions\FriendException;
use RTippin\Messenger\Exceptions\ProviderNotFoundException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreFriendRequestTest extends FeatureTestCase
{
    private MessengerProvider $tippin;
    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->doe = $this->userDoe();
    }

    /** @test */
    public function it_stores_sent_friend()
    {
        Messenger::setProvider($this->tippin);

        app(StoreFriendRequest::class)->withoutDispatches()->execute([
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ]);

        $this->assertDatabaseHas('pending_friends', [
            'sender_id' => $this->tippin->getKey(),
            'sender_type' => get_class($this->tippin),
            'recipient_id' => $this->doe->getKey(),
            'recipient_type' => get_class($this->doe),
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        Messenger::setProvider($this->tippin);

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
    }

    /** @test */
    public function it_throws_exception_if_provider_not_found()
    {
        Messenger::setProvider($this->tippin);

        $this->expectException(ProviderNotFoundException::class);
        $this->expectExceptionMessage('We were unable to locate the recipient you requested.');

        app(StoreFriendRequest::class)->withoutDispatches()->execute([
            'recipient_id' => 404,
            'recipient_alias' => 'user',
        ]);
    }

    /** @test */
    public function it_throws_exception_if_adding_yourself()
    {
        Messenger::setProvider($this->tippin);

        $this->expectException(FriendException::class);
        $this->expectExceptionMessage('Cannot friend yourself.');

        app(StoreFriendRequest::class)->withoutDispatches()->execute([
            'recipient_id' => $this->tippin->getKey(),
            'recipient_alias' => 'user',
        ]);
    }

    /** @test */
    public function it_throws_exception_if_already_friends()
    {
        Messenger::setProvider($this->tippin);
        $this->createFriends($this->tippin, $this->doe);

        $this->expectException(FriendException::class);
        $this->expectExceptionMessage('You are already friends, or have a pending request with John Doe.');

        app(StoreFriendRequest::class)->withoutDispatches()->execute([
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ]);
    }

    /** @test */
    public function it_throws_exception_if_sent_friend_exist()
    {
        Messenger::setProvider($this->tippin);
        SentFriend::create([
            'sender_id' => $this->tippin->getKey(),
            'sender_type' => get_class($this->tippin),
            'recipient_id' => $this->doe->getKey(),
            'recipient_type' => get_class($this->doe),
        ]);

        $this->expectException(FriendException::class);
        $this->expectExceptionMessage('You are already friends, or have a pending request with John Doe.');

        app(StoreFriendRequest::class)->withoutDispatches()->execute([
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ]);
    }

    /** @test */
    public function it_throws_exception_if_pending_friend_exist()
    {
        Messenger::setProvider($this->tippin);
        PendingFriend::create([
            'sender_id' => $this->doe->getKey(),
            'sender_type' => get_class($this->doe),
            'recipient_id' => $this->tippin->getKey(),
            'recipient_type' => get_class($this->tippin),
        ]);

        $this->expectException(FriendException::class);
        $this->expectExceptionMessage('You are already friends, or have a pending request with John Doe.');

        app(StoreFriendRequest::class)->withoutDispatches()->execute([
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ]);
    }

    /** @test */
    public function it_throws_exception_if_disabled_in_provider_interactions()
    {
        $providers = $this->getBaseProvidersConfig();
        $providers['user']['provider_interactions']['can_friend'] = false;
        Messenger::setMessengerProviders($providers);
        Messenger::setProvider($this->tippin);

        $this->expectException(FriendException::class);
        $this->expectExceptionMessage('Not authorized to add friend.');

        app(StoreFriendRequest::class)->withoutDispatches()->execute([
            'recipient_id' => $this->companyDevelopers()->getKey(),
            'recipient_alias' => 'company',
        ]);
    }
}
