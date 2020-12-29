<?php

namespace RTippin\Messenger\Tests;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\stubs\CompanyModel;
use RTippin\Messenger\Tests\stubs\UserModel;

trait HelperTrait
{
    protected function generateJaneSmith(): UserModel
    {
        return UserModel::create([
            'name' => 'Jane Smith',
            'email' => 'smith@example.net',
            'password' => 'secret',
        ]);
    }

    protected function generateSomeCompany(): CompanyModel
    {
        return CompanyModel::create([
            'company_name' => 'Some Company',
            'company_email' => 'company@example.net',
            'password' => 'secret',
        ]);
    }

    protected function makeFriends(MessengerProvider $one, MessengerProvider $two): array
    {
        $friendOne = Friend::create([
            'owner_id' => $one->getKey(),
            'owner_type' => get_class($one),
            'party_id' => $two->getKey(),
            'party_type' => get_class($two),
        ]);

        $friendTwo = Friend::create([
            'owner_id' => $two->getKey(),
            'owner_type' => get_class($two),
            'party_id' => $one->getKey(),
            'party_type' => get_class($one),
        ]);

        return [
            $friendOne,
            $friendTwo,
        ];
    }

    protected function makePrivateThread(MessengerProvider $one, MessengerProvider $two): Thread
    {
        $private = Thread::create(Definitions::DefaultThread);

        $private->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => $one->getKey(),
                'owner_type' => get_class($one),
            ]));

        $private->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => $two->getKey(),
                'owner_type' => get_class($two),
            ]));

        return $private;
    }
}
