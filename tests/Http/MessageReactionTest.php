<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class MessageReactionTest extends HttpTestCase
{
    /** @test */
    public function non_participant_is_forbidden_to_react()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.reactions.store', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]), [
            'reaction' => ':100:',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_is_forbidden_to_view_reacts()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.reactions.index', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_can_react_to_other_message()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.reactions.store', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]), [
            'reaction' => ':100:',
        ])
            ->assertSuccessful()
            ->assertJson([
                'message_id' => $message->id,
                'reaction' => ':100:',
            ]);
    }

    /** @test */
    public function user_can_react_to_own_message()
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.reactions.store', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]), [
            'reaction' => ':joy:',
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function it_condenses_reactions_and_owners()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $owner = [
            'owner' => [
                'provider_id',
                'provider_alias',
                'avatar',
            ],
        ];
        MessageReaction::factory()
            ->for($message)
            ->owner($this->tippin)
            ->sequence(
                ['reaction' => ':one:'],
                ['reaction' => ':two:'],
                ['reaction' => ':three:'],
                ['reaction' => ':four:'],
                ['reaction' => ':five:'],
            )
            ->count(5)
            ->create();
        MessageReaction::factory()
            ->for($message)
            ->owner($this->doe)
            ->sequence(
                ['reaction' => ':one:'],
                ['reaction' => ':two:'],
                ['reaction' => ':three:'],
                ['reaction' => ':four:'],
                ['reaction' => ':five:'],
                ['reaction' => ':six:'],
            )
            ->count(6)
            ->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.reactions.index', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(6, 'data')
            ->assertJsonStructure([
                'data' => [
                    ':one:' => [
                        $owner,
                        $owner,
                    ],
                    ':two:' => [
                        $owner,
                        $owner,
                    ],
                    ':three:' => [
                        $owner,
                        $owner,
                    ],
                    ':four:' => [
                        $owner,
                        $owner,
                    ],
                    ':five:' => [
                        $owner,
                        $owner,
                    ],
                    ':six:' => [
                        $owner,
                    ],
                ],
            ])
            ->assertJson([
                'meta' => [
                    'total' => 11,
                    'total_unique' => 6,
                ],
            ]);
    }

    /** @test */
    public function forbidden_to_react_when_disabled_in_config()
    {
        Messenger::setMessageReactions(false);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.reactions.store', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]), [
            'reaction' => ':joy:',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_remove_own_reaction_when_disabled_in_config()
    {
        $this->logCurrentRequest();
        Messenger::setMessageReactions(false);
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $reaction = MessageReaction::factory()->for($message)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.messages.reactions.destroy', [
            'thread' => $thread->id,
            'message' => $message->id,
            'reaction' => $reaction->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_can_remove_own_reaction()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $reaction = MessageReaction::factory()->for($message)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.messages.reactions.destroy', [
            'thread' => $thread->id,
            'message' => $message->id,
            'reaction' => $reaction->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function user_forbidden_to_remove_unowned_reaction()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $message = Message::factory()->for($thread)->owner($this->doe)->create();
        $reaction = MessageReaction::factory()->for($message)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.messages.reactions.destroy', [
            'thread' => $thread->id,
            'message' => $message->id,
            'reaction' => $reaction->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_remove_unowned_reaction()
    {
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $message = Message::factory()->for($thread)->owner($this->doe)->create();
        $reaction = MessageReaction::factory()->for($message)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.messages.reactions.destroy', [
            'thread' => $thread->id,
            'message' => $message->id,
            'reaction' => $reaction->id,
        ]))
            ->assertStatus(204);
    }

    /**
     * @test
     *
     * @dataProvider passesEmojiValidation
     *
     * @param  $string
     */
    public function it_passes_validating_has_valid_emoji($string)
    {
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.reactions.store', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]), [
            'reaction' => $string,
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     *
     * @dataProvider failsEmojiValidation
     *
     * @param  $string
     */
    public function it_fails_validating_has_valid_emoji($string)
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.messages.reactions.store', [
            'thread' => $thread->id,
            'message' => $message->id,
        ]), [
            'reaction' => $string,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reaction');
    }

    public static function passesEmojiValidation(): array
    {
        return [
            'Basic emoji shortcode' => [':poop:'],
            'Basic emoji utf8' => ['💩'],
            'Basic unicode emoji (:x:)' => ["\xE2\x9D\x8C"],
            'Basic ascii emoji' => [':)'],
            'Emoji found within string' => ['I tried to break :poop:'],
            'Emoji found within string after failed emoji' => ['I tried to break :unknown: :poop:'],
            'Multiple emojis it will pick first' => ['💩 :poop: 😁'],
        ];
    }

    public static function failsEmojiValidation(): array
    {
        return [
            'Unknown emoji shortcode' => [':unknown:'],
            'String with no emojis' => ['I have no emojis'],
            'Invalid if shortcode spaced' => [': poop :'],
            'Cannot be empty' => [''],
            'Cannot be null' => [null],
            'Cannot be array' => [[0, 1]],
            'Cannot be integer' => [1],
            'Cannot be boolean' => [false],
        ];
    }
}
