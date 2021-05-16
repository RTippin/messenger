<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PrivateMessageTest extends FeatureTestCase
{
    /** @test */
    public function non_participant_is_forbidden()
    {
        $thread = Thread::factory()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.index', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_can_view_messages()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.index', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function user_can_view_message()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.show', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $message->id,
            ]);
    }

    /** @test */
    public function user_forbidden_to_send_message_when_thread_locked()
    {
        $thread = Thread::factory()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_can_send_message()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $thread->id,
                'temporary_id' => '123-456-789',
                'type' => 0,
                'type_verbose' => 'MESSAGE',
                'body' => 'Hello!',
            ]);
    }

    /** @test */
    public function user_can_send_message_when_thread_awaiting_recipient_approval()
    {
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function non_participant_forbidden_to_send_message()
    {
        $thread = Thread::factory()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function recipient_forbidden_to_send_message_when_thread_awaiting_approval()
    {
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function message_owner_can_archive_message()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.messages.destroy', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function non_owner_forbidden_to_archive_message()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.messages.destroy', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_archive_message_when_thread_locked()
    {
        $thread = Thread::factory()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.messages.destroy', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertForbidden();
    }

    /**
     * @test
     * @dataProvider messageFailsValidation
     * @param $messageValue
     * @param $tempIdValue
     */
    public function send_message_fails_validations($messageValue, $tempIdValue)
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
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
     * @dataProvider messageExtraFailsValidation
     * @param $extraValue
     */
    public function send_message_extra_fails_validation($extraValue)
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
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

    /**
     * @test
     * @dataProvider messageExtraPassesValidation
     * @param $extraValue
     * @param $extraOutput
     */
    public function send_message_extra_passes_validation($extraValue, $extraOutput)
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $thread->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
            'extra' => $extraValue,
        ])
            ->assertSuccessful()
            ->assertJson([
                'extra' => $extraOutput,
            ]);
    }

    public function messageFailsValidation(): array
    {
        return [
            'Fields cannot be empty' => ['', ''],
            'Fields cannot be integers' => [5, 5],
            'Fields cannot be boolean' => [true, true],
            'Fields cannot be null' => [null, null],
            'Fields cannot be an array' => [[1, 2], [1, 2]],
        ];
    }

    public function messageExtraFailsValidation(): array
    {
        return [
            'Cannot be string' => ['yes'],
            'Cannot be boolean' => [true],
            'Cannot be integer' => [5],
        ];
    }

    public function messageExtraPassesValidation(): array
    {
        return [
            'Can be array' => [['testing' => true], ['testing' => true]],
            'Can be multidimensional array' => [['testing' => true, 'more' => ['test' => true]], ['testing' => true, 'more' => ['test' => true]]],
            'Can be JSON string' => ['{"testing":true}', ['testing' => true]],
            'Can be JSON string array' => ['[{"testing":true,"more":[0,1,2]}]', [['testing' => true, 'more' => [0, 1, 2]]]],
            'Can be null' => [null, null],
        ];
    }
}
