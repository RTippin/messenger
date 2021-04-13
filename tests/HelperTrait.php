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
use RTippin\Messenger\Tests\Fixtures\CompanyModelUuid;
use RTippin\Messenger\Tests\Fixtures\UserModel;
use RTippin\Messenger\Tests\Fixtures\UserModelUuid;

trait HelperTrait
{
    /**
     * @return MessengerProvider|UserModel|UserModelUuid
     */
    protected function userTippin()
    {
        return $this->getModelUser()::where('email', '=', 'tippindev@gmail.com')->first();
    }

    /**
     * @return MessengerProvider|UserModel|UserModelUuid
     */
    protected function userDoe()
    {
        return $this->getModelUser()::where('email', '=', 'doe@example.net')->first();
    }

    /**
     * @return MessengerProvider|CompanyModel|CompanyModelUuid
     */
    protected function companyDevelopers()
    {
        return $this->getModelCompany()::where('company_email', '=', 'developers@example.net')->first();
    }

    /**
     * @return MessengerProvider|UserModel|UserModelUuid
     */
    protected function createJaneSmith()
    {
        return $this->getModelUser()::create([
            'name' => 'Jane Smith',
            'email' => 'smith@example.net',
            'password' => 'secret',
        ]);
    }

    /**
     * @return MessengerProvider|CompanyModel|CompanyModelUuid
     */
    protected function createSomeCompany()
    {
        return $this->getModelCompany()::create([
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

    protected function createPrivateThread($one, $two, bool $pending = false): Thread
    {
        $private = Thread::factory()->create();

        Participant::factory()
            ->for($private)
            ->for($one, 'owner')
            ->create([
                'pending' => $pending,
            ]);

        Participant::factory()
            ->for($private)
            ->for($two, 'owner')
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
            ->for($admin, 'owner')
            ->admin()
            ->create();

        foreach ($participants as $participant) {
            Participant::factory()
                ->for($group)
                ->for($participant, 'owner')
                ->create();
        }

        return $group;
    }

    protected function createMessage($thread, $owner): Message
    {
        return Message::factory()
            ->for($thread)
            ->for($owner, 'owner')
            ->create([
                'body' => 'First Test Message',
            ]);
    }

    protected function createCall($thread, $owner, ...$participants): Call
    {
        $call = Call::factory()
            ->for($thread)
            ->for($owner, 'owner')
            ->setup()
            ->create();

        CallParticipant::factory()
            ->for($call)
            ->for($owner, 'owner')
            ->create();

        foreach ($participants as $participant) {
            CallParticipant::factory()
                ->for($call)
                ->for($participant, 'owner')
                ->create();
        }

        return $call;
    }
}
