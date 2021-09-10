<?php

namespace RTippin\Messenger;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Policies\BotActionPolicy;
use RTippin\Messenger\Policies\BotPolicy;
use RTippin\Messenger\Policies\CallParticipantPolicy;
use RTippin\Messenger\Policies\CallPolicy;
use RTippin\Messenger\Policies\FriendPolicy;
use RTippin\Messenger\Policies\InvitePolicy;
use RTippin\Messenger\Policies\MessagePolicy;
use RTippin\Messenger\Policies\MessageReactionPolicy;
use RTippin\Messenger\Policies\ParticipantPolicy;
use RTippin\Messenger\Policies\PendingFriendPolicy;
use RTippin\Messenger\Policies\SentFriendPolicy;
use RTippin\Messenger\Policies\ThreadPolicy;

/**
 * @property-read Application $app
 */
trait PolicyMap
{
    /**
     * The policy mappings for messenger models.
     *
     * @var array
     */
    private array $policies = [
        Bot::class => BotPolicy::class,
        BotAction::class => BotActionPolicy::class,
        Call::class => CallPolicy::class,
        CallParticipant::class => CallParticipantPolicy::class,
        Thread::class => ThreadPolicy::class,
        Participant::class => ParticipantPolicy::class,
        Message::class => MessagePolicy::class,
        MessageReaction::class => MessageReactionPolicy::class,
        Invite::class => InvitePolicy::class,
        Friend::class => FriendPolicy::class,
        PendingFriend::class => PendingFriendPolicy::class,
        SentFriend::class => SentFriendPolicy::class,
    ];

    /**
     * Register the application's policies.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    private function registerPolicies(): void
    {
        $gate = $this->app->make(Gate::class);

        foreach ($this->policies as $key => $value) {
            $gate->policy($key, $value);
        }
    }
}
