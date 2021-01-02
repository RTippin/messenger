<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\MessageArchivedBroadcast;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\MessageArchivedEvent;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PrivateMessageTest extends FeatureTestCase
{
    private Thread $private;

    private Message $message;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $this->private = $this->createPrivateThread(
            $tippin,
            $this->userDoe()
        );

        $this->message = $this->createMessage(
            $this->private,
            $tippin
        );
    }

    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.threads.messages.index', [
            'thread' => $this->private->id,
        ]))
            ->assertUnauthorized();
    }

    /** @test */
    public function non_participant_is_forbidden()
    {
        $this->actingAs($this->companyDevelopers());

        $this->getJson(route('api.messenger.threads.messages.index', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function recipient_can_view_messages_index()
    {
        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.messages.index', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function user_can_view_message()
    {
        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.threads.messages.show', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->message->id,
                'body' => 'First Test Message',
            ]);
    }

    /** @test */
    public function recipient_can_view_message()
    {
        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.messages.show', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->message->id,
                'body' => 'First Test Message',
            ]);
    }

    /** @test */
    public function user_can_send_message()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->private->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->private->id,
                'temporary_id' => '123-456-789',
                'type' => 0,
                'type_verbose' => 'MESSAGE',
                'body' => 'Hello!',
                'owner' => [
                    'provider_id' => $tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ]);

        Event::assertDispatched(function (NewMessageBroadcast $event) use ($doe, $tippin) {
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-user.'.$tippin->getKey(), $event->broadcastOn());
            $this->assertEquals($this->private->id, $event->broadcastWith()['thread_id']);
            $this->assertEquals('123-456-789', $event->broadcastWith()['temporary_id']);

            return true;
        });

        Event::assertDispatched(function (NewMessageEvent $event) {
            return $this->private->id === $event->message->thread_id;
        });
    }

    /** @test */
    public function recipient_can_send_message()
    {
        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->actingAs($this->userDoe());

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->private->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function sender_can_send_message_when_thread_awaiting_recipient_approval()
    {
        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->private->participants()
            ->where('owner_id', '=', $this->userDoe()->getKey())
            ->first()
            ->update([
                'pending' => true,
            ]);

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->private->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function non_participant_forbidden_to_send_message()
    {
        $this->actingAs($this->companyDevelopers());

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->private->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function recipient_forbidden_to_send_message_when_thread_awaiting_approval_on_them()
    {
        $doe = $this->userDoe();

        $this->private->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->first()
            ->update([
                'pending' => true,
            ]);

        $this->actingAs($doe);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->private->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function sender_can_archive_message()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Event::fake([
            MessageArchivedBroadcast::class,
            MessageArchivedEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->deleteJson(route('api.messenger.threads.messages.destroy', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertSuccessful();

        Event::assertDispatched(function (MessageArchivedBroadcast $event) use ($doe, $tippin) {
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-user.'.$tippin->getKey(), $event->broadcastOn());
            $this->assertEquals($this->message->id, $event->broadcastWith()['message_id']);

            return true;
        });

        Event::assertDispatched(function (MessageArchivedEvent $event) {
            return $this->message->id === $event->message->id;
        });
    }

    /** @test */
    public function recipient_forbidden_to_archive_message()
    {
        $this->actingAs($this->userDoe());

        $this->deleteJson(route('api.messenger.threads.messages.destroy', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertForbidden();
    }

    /**
     * @test
     * @dataProvider messageValidation
     * @param $messageValue
     * @param $tempIdValue
     */
    public function send_message_validates_request($messageValue, $tempIdValue)
    {
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->private->id,
        ]), [
            'message' => $messageValue,
            'temporary_id' => $tempIdValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'message',
                'temporary_id',
            ]);
    }

    public function messageValidation(): array
    {
        return [
            'Fields cannot be empty' => ['', ''],
            'Fields cannot be integers' => [5, 5],
            'Fields cannot be boolean' => [true, true],
            'Fields cannot be null' => [null, null],
            'Fields cannot be an array' => [[1, 2], [1, 2]],
        ];
    }
}
