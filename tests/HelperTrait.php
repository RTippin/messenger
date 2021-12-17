<?php

namespace RTippin\Messenger\Tests;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\DataTransferObjects\ResolvedBotHandlerDTO;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Messenger;
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

    protected function makeResolvedBotHandlerDTO(string $handler,
                                                 string $match,
                                                 bool $enabled,
                                                 bool $adminOnly,
                                                 int $cooldown,
                                                 ?string $triggers = null,
                                                 ?string $payload = null): ResolvedBotHandlerDTO
    {
        return new ResolvedBotHandlerDTO(
            MessengerBots::getHandler($handler),
            $match,
            $enabled,
            $adminOnly,
            $cooldown,
            $triggers,
            $payload
        );
    }

    public function modelsWithOwner(): array
    {
        return [
            'Bot Model' => [
                fn ($tippin) => Bot::factory()
                    ->for(Thread::factory()->create())
                    ->owner($tippin)
                    ->create(),
            ],
            'BotAction Model' => [
                fn ($tippin) => BotAction::factory()
                    ->for(
                        Bot::factory()->for(
                            Thread::factory()->group()->create()
                        )->owner($tippin)->create()
                    )->owner($tippin)->create(),
            ],
            'Call Model' => [
                fn ($tippin) => Call::factory()
                    ->for(Thread::factory()->create())
                    ->owner($tippin)
                    ->create(),
            ],
            'CallParticipant Model' => [
                fn ($tippin) => CallParticipant::factory()
                    ->for(
                        Call::factory()->for(
                            Thread::factory()->create()
                        )->owner($tippin)->create()
                    )->owner($tippin)->create(),
            ],
            'Friend Model' => [
                fn ($tippin) => Friend::factory()
                    ->providers($tippin, $tippin)
                    ->create(),
            ],
            'Invite Model' => [
                fn ($tippin) => Invite::factory()
                    ->for(Thread::factory()->group()->create())
                    ->owner($tippin)
                    ->create(),
            ],
            'Message Model' => [
                fn ($tippin) => Message::factory()
                    ->for(Thread::factory()->create())
                    ->owner($tippin)
                    ->create(),
            ],
            'MessageReaction Model' => [
                fn ($tippin) => MessageReaction::factory()
                    ->for(
                        Message::factory()->for(
                            Thread::factory()->create()
                        )->owner($tippin)->create()
                    )->owner($tippin)->create(),
            ],
            'Messenger Model' => [
                fn ($tippin) => Messenger::factory()
                    ->owner($tippin)
                    ->create(),
            ],
            'Participant Model' => [
                fn ($tippin) => Participant::factory()
                    ->for(Thread::factory()->create())
                    ->owner($tippin)
                    ->admin()
                    ->create(),
            ],
        ];
    }
}
