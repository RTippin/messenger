<?php

namespace RTippin\Messenger\Tests\Commands;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeMessagesCommandTest extends FeatureTestCase
{
    private MessengerProvider $tippin;
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->group = $this->createGroupThread($this->tippin);
    }

    /** @test */
    public function it_doesnt_find_messages()
    {
        $this->artisan('messenger:purge:messages')
            ->expectsOutput('No messages archived 30 days or greater found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_set_days()
    {
        $this->artisan('messenger:purge:messages', [
            '--days' => 10,
        ])
            ->expectsOutput('No messages archived 10 days or greater found.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_removes_message()
    {
        $message = $this->group->messages()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'type' => 0,
            'body' => 'test',
            'deleted_at' => now()->subMonths(2),
        ]);

        $this->artisan('messenger:purge:messages')
            ->expectsOutput('1 messages archived 30 days or greater have been purged!')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('messages', [
            'id' => $message->id,
        ]);
    }

    /** @test */
    public function it_removes_multiple_messages()
    {
        $this->group->messages()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'type' => 0,
            'body' => 'test',
            'deleted_at' => now()->subDays(10),
        ]);
        $this->group->messages()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'type' => 0,
            'body' => 'test',
            'deleted_at' => now()->subDays(8),
        ]);

        $this->artisan('messenger:purge:messages', [
            '--days' => 7,
        ])
            ->expectsOutput('2 messages archived 7 days or greater have been purged!')
            ->assertExitCode(0);

        $this->assertDatabaseCount('messages', 0);
    }
}
