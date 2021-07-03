<?php

namespace RTippin\Messenger\Tests;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\UserModel;

trait HelperTrait
{
    /**
     * @return MessengerProvider|UserModel
     */
    protected function createJaneSmith()
    {
        return UserModel::create([
            'name' => 'Jane Smith',
            'email' => 'smith@example.net',
            'password' => 'secret',
        ]);
    }

    /**
     * @return MessengerProvider|CompanyModel
     */
    protected function createSomeCompany()
    {
        return CompanyModel::create([
            'company_name' => 'Some Company',
            'company_email' => 'company@example.net',
            'password' => 'secret',
        ]);
    }

    protected function createFriends($one, $two): array
    {
        return [
            Friend::factory()->providers($one, $two)->create(),
            Friend::factory()->providers($two, $one)->create(),
        ];
    }

    protected function createPrivateThread($one, $two): Thread
    {
        $private = Thread::factory()->create();
        Participant::factory()
            ->for($private)
            ->owner($one)
            ->create();
        Participant::factory()
            ->for($private)
            ->owner($two)
            ->create();

        return $private;
    }

    protected function createGroupThread($admin, ...$participants): Thread
    {
        $group = Thread::factory()
            ->group()
            ->create([
                'subject' => 'First Test Group',
                'image' => '5.png',
            ]);
        Participant::factory()
            ->for($group)
            ->owner($admin)
            ->admin()
            ->create();

        foreach ($participants as $participant) {
            Participant::factory()
                ->for($group)
                ->owner($participant)
                ->create();
        }

        return $group;
    }

    protected function createMessage($thread, $owner): Message
    {
        return Message::factory()
            ->for($thread)
            ->owner($owner)
            ->create([
                'body' => 'First Test Message',
            ]);
    }

    protected function createCall($thread, $owner, ...$participants): Call
    {
        $call = Call::factory()
            ->for($thread)
            ->owner($owner)
            ->setup()
            ->create();
        CallParticipant::factory()
            ->for($call)
            ->owner($owner)
            ->create();

        foreach ($participants as $participant) {
            CallParticipant::factory()
                ->for($call)
                ->owner($participant)
                ->create();
        }

        return $call;
    }
}
