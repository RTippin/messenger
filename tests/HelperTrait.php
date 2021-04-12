<?php

namespace RTippin\Messenger\Tests;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\Definitions;
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
            'messaging' => true,
            'lockout' => false,
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
            'edited' => false,
            'reacted' => false,
            'embeds' => true,
        ]);
    }

    protected function createCall(Thread $thread, MessengerProvider $owner, ...$participants): Call
    {
        $call = $thread->calls()->create([
            'type' => 1,
            'owner_id' => $owner->getKey(),
            'owner_type' => get_class($owner),
            'call_ended' => null,
            'setup_complete' => true,
            'teardown_complete' => false,
            'room_id' => '123456789',
            'room_pin' => 'PIN',
            'room_secret' => 'SECRET',
            'payload' => 'PAYLOAD',
        ]);

        $call->participants()->create([
            'owner_id' => $owner->getKey(),
            'owner_type' => get_class($owner),
        ]);

        foreach ($participants as $participant) {
            $call->participants()->create([
                'owner_id' => $participant->getKey(),
                'owner_type' => get_class($participant),
            ]);
        }

        return $call;
    }
}
