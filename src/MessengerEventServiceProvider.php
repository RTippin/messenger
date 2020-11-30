<?php

namespace RTippin\Messenger;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Events\CallLeftEvent;
use RTippin\Messenger\Events\CallStartedEvent;
use RTippin\Messenger\Events\DemotedAdminEvent;
use RTippin\Messenger\Events\InviteUsedEvent;
use RTippin\Messenger\Events\ParticipantsAddedEvent;
use RTippin\Messenger\Events\PromotedAdminEvent;
use RTippin\Messenger\Events\RemovedFromThreadEvent;
use RTippin\Messenger\Events\ThreadArchivedEvent;
use RTippin\Messenger\Events\ThreadAvatarEvent;
use RTippin\Messenger\Events\ThreadLeftEvent;
use RTippin\Messenger\Events\ThreadSettingsEvent;
use RTippin\Messenger\Listeners\ArchiveEmptyThread;
use RTippin\Messenger\Listeners\CallEndedMessage;
use RTippin\Messenger\Listeners\DemotedAdminMessage;
use RTippin\Messenger\Listeners\EndCallIfEmpty;
use RTippin\Messenger\Listeners\JoinedWithInviteMessage;
use RTippin\Messenger\Listeners\ParticipantsAddedMessage;
use RTippin\Messenger\Listeners\PromotedAdminMessage;
use RTippin\Messenger\Listeners\RemovedFromThreadMessage;
use RTippin\Messenger\Listeners\SetupCall;
use RTippin\Messenger\Listeners\TeardownCall;
use RTippin\Messenger\Listeners\ThreadArchivedMessage;
use RTippin\Messenger\Listeners\ThreadAvatarMessage;
use RTippin\Messenger\Listeners\ThreadLeftMessage;
use RTippin\Messenger\Listeners\ThreadNameMessage;

class MessengerEventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        CallStartedEvent::class => [
            SetupCall::class,
        ],
        CallLeftEvent::class => [
            EndCallIfEmpty::class,
        ],
        CallEndedEvent::class => [
            TeardownCall::class,
            CallEndedMessage::class,
        ],
        DemotedAdminEvent::class => [
            DemotedAdminMessage::class,
        ],
        InviteUsedEvent::class => [
            JoinedWithInviteMessage::class,
        ],
        ParticipantsAddedEvent::class => [
            ParticipantsAddedMessage::class,
        ],
        PromotedAdminEvent::class => [
            PromotedAdminMessage::class,
        ],
        RemovedFromThreadEvent::class => [
            RemovedFromThreadMessage::class,
        ],
        ThreadLeftEvent::class => [
            ThreadLeftMessage::class,
            ArchiveEmptyThread::class,
        ],
        ThreadArchivedEvent::class => [
            ThreadArchivedMessage::class,
        ],
        ThreadAvatarEvent::class => [
            ThreadAvatarMessage::class,
        ],
        ThreadSettingsEvent::class => [
            ThreadNameMessage::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
