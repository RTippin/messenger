<?php

namespace RTippin\Messenger\Tests;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\stubs\CompanyModel;
use RTippin\Messenger\Tests\stubs\CompanyModelUuid;
use RTippin\Messenger\Tests\stubs\UserModel;
use RTippin\Messenger\Tests\stubs\UserModelUuid;

trait HelperTrait
{
    protected function userTippin()
    {
        /** @var UserModel|UserModelUuid $model */
        $model = self::UseUUID ? UserModelUuid::class : UserModel::class;

        return $model::where('email', '=', 'richard.tippin@gmail.com')
            ->first();
    }

    protected function userDoe()
    {
        /** @var UserModel|UserModelUuid $model */
        $model = self::UseUUID ? UserModelUuid::class : UserModel::class;

        return $model::where('email', '=', 'doe@example.net')
            ->first();
    }

    protected function companyDevelopers()
    {
        /** @var CompanyModel|CompanyModelUuid $model */
        $model = self::UseUUID ? CompanyModelUuid::class : CompanyModel::class;

        return $model::where('company_email', '=', 'developers@example.net')
            ->first();
    }

    protected function companyLaravel()
    {
        /** @var CompanyModel|CompanyModelUuid $model */
        $model = self::UseUUID ? CompanyModelUuid::class : CompanyModel::class;

        return $model::where('company_email', '=', 'laravel@example.net')
            ->first();
    }

    protected function createJaneSmith()
    {
        $jane = [
            'name' => 'Jane Smith',
            'email' => 'smith@example.net',
            'password' => 'secret',
        ];

        return self::UseUUID
            ? UserModelUuid::create($jane)
            : UserModel::create($jane);
    }

    protected function createSomeCompany()
    {
        $someCompany = [
            'company_name' => 'Some Company',
            'company_email' => 'company@example.net',
            'password' => 'secret',
        ];

        return self::UseUUID
            ? CompanyModelUuid::create($someCompany)
            : CompanyModel::create($someCompany);
    }

    protected function createFriends(MessengerProvider $one, MessengerProvider $two): array
    {
        return [
            Friend::create([
                'owner_id' => $one->getKey(),
                'owner_type' => get_class($one),
                'party_id' => $two->getKey(),
                'party_type' => get_class($two),
            ]),
            Friend::create([
                'owner_id' => $two->getKey(),
                'owner_type' => get_class($two),
                'party_id' => $one->getKey(),
                'party_type' => get_class($one),
            ]),
        ];
    }

    protected function createPrivateThread(MessengerProvider $one, MessengerProvider $two, bool $pending = false): Thread
    {
        $private = Thread::create(Definitions::DefaultThread);

        $private->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => $one->getKey(),
                'owner_type' => get_class($one),
                'pending' => $pending,
            ]));

        $private->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => $two->getKey(),
                'owner_type' => get_class($two),
            ]));

        return $private;
    }

    protected function createGroupThread(MessengerProvider $admin, ...$participants): Thread
    {
        $group = Thread::create([
            'type' => 2,
            'subject' => 'First Test Group',
            'image' => '5.png',
            'add_participants' => true,
            'invitations' => true,
            'calling' => true,
            'knocks' => true,
        ]);

        $group->participants()
            ->create(array_merge(Definitions::DefaultAdminParticipant, [
                'owner_id' => $admin->getKey(),
                'owner_type' => get_class($admin),
            ]));

        foreach ($participants as $participant) {
            $group->participants()
                ->create(array_merge(Definitions::DefaultParticipant, [
                    'owner_id' => $participant->getKey(),
                    'owner_type' => get_class($participant),
                ]));
        }

        return $group;
    }

    protected function createMessage(Thread $thread, MessengerProvider $owner): Message
    {
        return $thread->messages()->create([
            'body' => 'First Test Message',
            'type' => 0,
            'owner_id' => $owner->getKey(),
            'owner_type' => get_class($owner),
        ]);
    }

    protected function createCall(Thread $thread, MessengerProvider $owner): Call
    {
        return $thread->calls()->create([
            'type' => 1,
            'owner_id' => $owner->getKey(),
            'owner_type' => get_class($owner),
        ]);
    }
}
