<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\MessageArchivedBroadcast;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\MessageArchivedEvent;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PrivateMessageTest extends FeatureTestCase
{
    private Thread $private;
    private Message $message;
    private MessengerProvider $tippin;
    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->doe = $this->userDoe();
        $this->private = $this->createPrivateThread($this->tippin, $this->doe);
        $this->message = $this->createMessage($this->private, $this->tippin);
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
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.messages.index', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function user_can_view_message()
    {
        $this->actingAs($this->tippin);

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
        $this->actingAs($this->doe);

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
    public function user_forbidden_to_send_message_when_thread_locked()
    {
        $this->doe->delete();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->private->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_can_send_message()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

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
                    'provider_id' => $this->tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ]);
    }

    /** @test */
    public function user_can_send_message_with_extra()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->private->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
            'extra' => ['test' => true],
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->private->id,
                'temporary_id' => '123-456-789',
                'type' => 0,
                'type_verbose' => 'MESSAGE',
                'body' => 'Hello!',
                'extra' => [
                    'test' => true,
                ],
                'owner' => [
                    'provider_id' => $this->tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ]);
    }

    /** @test */
    public function recipient_can_send_message()
    {
        $this->actingAs($this->doe);

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

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
        $doe = $this->userDoe();
        $this->private->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first()
            ->update([
                'pending' => true,
            ]);
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

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
        $this->private->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'pending' => true,
            ]);
        $this->actingAs($this->doe);

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
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            MessageArchivedBroadcast::class,
            MessageArchivedEvent::class,
        ]);

        $this->deleteJson(route('api.messenger.threads.messages.destroy', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function recipient_forbidden_to_archive_message()
    {
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.messages.destroy', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_archive_message_when_thread_locked()
    {
        $this->doe->delete();
        $this->actingAs($this->tippin);

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
        $this->actingAs($this->tippin);

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

    /**
     * @test
     * @dataProvider messageExtraValidation
     * @param $extraValue
     */
    public function send_message_validates_extra($extraValue)
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->private->id,
        ]), [
            'message' => 'test',
            'temporary_id' => '1234',
            'extra' => $extraValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'extra',
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

    public function messageExtraValidation(): array
    {
        return [
            'Cannot be string' => ['yes'],
            'Cannot be boolean' => [true],
            'Cannot be integer' => [5],
        ];
    }
}
