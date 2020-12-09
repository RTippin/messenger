<?php

namespace RTippin\Messenger;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Events\CallLeftEvent;
use RTippin\Messenger\Events\CallStartedEvent;
use RTippin\Messenger\Events\DemotedAdminEvent;
use RTippin\Messenger\Events\InviteUsedEvent;
use RTippin\Messenger\Events\ParticipantsAddedEvent;
use RTippin\Messenger\Events\PromotedAdminEvent;
use RTippin\Messenger\Events\RemovedFromThreadEvent;
use RTippin\Messenger\Events\StatusHeartbeatEvent;
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
use RTippin\Messenger\Listeners\StoreMessengerIp;
use RTippin\Messenger\Listeners\TeardownCall;
use RTippin\Messenger\Listeners\ThreadArchivedMessage;
use RTippin\Messenger\Listeners\ThreadAvatarMessage;
use RTippin\Messenger\Listeners\ThreadLeftMessage;
use RTippin\Messenger\Listeners\ThreadNameMessage;

/**
 * @property-read Application $app
 */
trait EventMap
{
    /**
     * The event listener mappings for Messenger.
     *
     * @var array
     */
    protected array $events = [
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
        StatusHeartbeatEvent::class => [
            StoreMessengerIp::class,
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
     * Register the Event / Listener mappings.
     *
     * @return void
     * @throws BindingResolutionException
     */
    protected function registerEvents()
    {
        if($this->app['config']->get('messenger.queued_event_listeners'))
        {
            $events = $this->app->make(Dispatcher::class);

            foreach ($this->events as $event => $listeners) {
                foreach ($listeners as $listener) {
                    $events->listen($event, $listener);
                }
            }
        }
    }
}