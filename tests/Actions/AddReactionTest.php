<?php

namespace RTippin\Messenger\Tests\Actions;

use RTippin\Messenger\Actions\Messages\AddReaction;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\ReactionException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class AddReactionTest extends FeatureTestCase
{
    private Thread $group;
    private Message $message;
    private MessengerProvider $tippin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->group = $this->createGroupThread($this->tippin);
        $this->message = $this->createMessage($this->group, $this->tippin);
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setMessageReactions(false);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Message reactions are currently disabled.');

        app(AddReaction::class)->withoutDispatches()->execute(
            $this->group,
            $this->message,
            ':joy:'
        );
    }

    /** @test */
    public function it_throws_exception_if_no_valid_emojis()
    {
        $this->expectException(ReactionException::class);
        $this->expectExceptionMessage('No valid reactions found.');

        app(AddReaction::class)->withoutDispatches()->execute(
            $this->group,
            $this->message,
            ':unknown:'
        );
    }

    /** @test */
    public function it_throws_exception_if_reaction_already_exist()
    {
        $this->message->reactions()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'reaction' => ':joy:',
            'created_at' => now(),
        ]);

        $this->assertTrue(true);

        $this->expectException(ReactionException::class);
        $this->expectExceptionMessage('You have already used that reaction.');

        app(AddReaction::class)->withoutDispatches()->execute(
            $this->group,
            $this->message,
            ':joy:'
        );
    }
}
