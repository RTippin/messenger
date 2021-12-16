<?php

namespace RTippin\Messenger\Tests\Support;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use InvalidArgumentException;
use RTippin\Messenger\Broadcasting\ClientEvents\Read;
use RTippin\Messenger\Broadcasting\ClientEvents\StopTyping;
use RTippin\Messenger\Broadcasting\ClientEvents\Typing;
use RTippin\Messenger\Broadcasting\MessengerBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\PresenceEvents;
use RTippin\Messenger\Tests\FeatureTestCase;

class PresenceEventsTest extends FeatureTestCase
{
    protected function tearDown(): void
    {
        PresenceEvents::reset();

        parent::tearDown();
    }

    /** @test */
    public function it_can_get_default_typing_class()
    {
        $this->assertSame(Typing::class, PresenceEvents::getTypingClass());
    }

    /** @test */
    public function it_can_set_typing_class()
    {
        PresenceEvents::setTypingClass(PresenceEvent::class);

        $this->assertSame(PresenceEvent::class, PresenceEvents::getTypingClass());
    }

    /** @test */
    public function it_throws_exception_if_typing_class_doesnt_extend_ours()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(InvalidEvent::class.' must extend '.MessengerBroadcast::class);

        PresenceEvents::setTypingClass(InvalidEvent::class);
    }

    /** @test */
    public function it_uses_default_typing_data()
    {
        $expected = [
            'provider_id' => $this->tippin->getKey(),
            'provider_alias' => 'user',
            'name' => 'Richard Tippin',
            'avatar' => $this->tippin->getProviderAvatarRoute(),
        ];

        $this->assertSame($expected, PresenceEvents::makeTypingEvent($this->tippin));
    }

    /** @test */
    public function it_can_set_typing_data()
    {
        PresenceEvents::setTypingClosure(function (MessengerProvider $provider) {
            return [
                'overrides' => true,
                'name' => $provider->getProviderName(),
            ];
        });

        $this->assertSame([
            'overrides' => true,
            'name' => 'Richard Tippin',
        ], PresenceEvents::makeTypingEvent($this->tippin));
    }

    /** @test */
    public function it_can_get_default_stop_typing_class()
    {
        $this->assertSame(StopTyping::class, PresenceEvents::getStopTypingClass());
    }

    /** @test */
    public function it_can_set_stop_typing_class()
    {
        PresenceEvents::setStopTypingClass(PresenceEvent::class);

        $this->assertSame(PresenceEvent::class, PresenceEvents::getStopTypingClass());
    }

    /** @test */
    public function it_throws_exception_if_stop_typing_class_doesnt_extend_ours()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(InvalidEvent::class.' must extend our base '.MessengerBroadcast::class);

        PresenceEvents::setStopTypingClass(InvalidEvent::class);
    }

    /** @test */
    public function it_uses_default_stop_typing_data()
    {
        $expected = [
            'provider_id' => $this->tippin->getKey(),
            'provider_alias' => 'user',
            'name' => 'Richard Tippin',
            'avatar' => $this->tippin->getProviderAvatarRoute(),
        ];

        $this->assertSame($expected, PresenceEvents::makeStopTypingEvent($this->tippin));
    }

    /** @test */
    public function it_can_set_stop_typing_data()
    {
        PresenceEvents::setStopTypingClosure(function (MessengerProvider $provider) {
            return [
                'overrides' => true,
                'name' => $provider->getProviderName(),
            ];
        });

        $this->assertSame([
            'overrides' => true,
            'name' => 'Richard Tippin',
        ], PresenceEvents::makeStopTypingEvent($this->tippin));
    }

    /** @test */
    public function it_can_get_default_read_class()
    {
        $this->assertSame(Read::class, PresenceEvents::getReadClass());
    }

    /** @test */
    public function it_can_set_read_class()
    {
        PresenceEvents::setReadClass(PresenceEvent::class);

        $this->assertSame(PresenceEvent::class, PresenceEvents::getReadClass());
    }

    /** @test */
    public function it_throws_exception_if_read_class_doesnt_extend_ours()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(InvalidEvent::class.' must extend our base '.MessengerBroadcast::class);

        PresenceEvents::setReadClass(InvalidEvent::class);
    }

    /** @test */
    public function it_uses_default_read_data()
    {
        $message = Message::factory()->for(Thread::factory()->create())->owner($this->tippin)->create();
        $expected = [
            'provider_id' => $this->tippin->getKey(),
            'provider_alias' => 'user',
            'name' => 'Richard Tippin',
            'avatar' => $this->tippin->getProviderAvatarRoute(),
            'message_id' => $message->id,
        ];

        $this->assertSame($expected, PresenceEvents::makeReadEvent($this->tippin, $message));
    }

    /** @test */
    public function it_can_set_read_data()
    {
        $message = Message::factory()->for(Thread::factory()->create())->owner($this->tippin)->create();
        PresenceEvents::setReadClosure(function (MessengerProvider $provider, Message $message) {
            return [
                'overrides' => true,
                'name' => $provider->getProviderName(),
                'the_message' => $message->id,
            ];
        });

        $this->assertSame([
            'overrides' => true,
            'name' => 'Richard Tippin',
            'the_message' => $message->id,
        ], PresenceEvents::makeReadEvent($this->tippin, $message));
    }

    /** @test */
    public function it_allows_null_message_in_read_data()
    {
        $expected = [
            'provider_id' => $this->tippin->getKey(),
            'provider_alias' => 'user',
            'name' => 'Richard Tippin',
            'avatar' => $this->tippin->getProviderAvatarRoute(),
            'message_id' => null,
        ];

        $this->assertSame($expected, PresenceEvents::makeReadEvent($this->tippin));
    }
}

class PresenceEvent extends MessengerBroadcast
{
    public function broadcastAs(): string
    {
        return 'client-fake';
    }
}

class InvalidEvent implements ShouldBroadcastNow
{
    public function broadcastAs(): string
    {
        return 'client-bad';
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
