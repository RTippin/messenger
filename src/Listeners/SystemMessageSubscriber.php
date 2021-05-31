<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Events\Dispatcher;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Events\DemotedAdminEvent;
use RTippin\Messenger\Events\InviteUsedEvent;
use RTippin\Messenger\Events\ParticipantsAddedEvent;
use RTippin\Messenger\Events\PromotedAdminEvent;
use RTippin\Messenger\Events\RemovedFromThreadEvent;
use RTippin\Messenger\Events\ThreadArchivedEvent;
use RTippin\Messenger\Events\ThreadAvatarEvent;
use RTippin\Messenger\Events\ThreadLeftEvent;
use RTippin\Messenger\Events\ThreadSettingsEvent;

class SystemMessageSubscriber
{
    /**
     * Register the listeners for the subscriber.
     *
     * @param Dispatcher $events
     * @return void
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(CallEndedEvent::class, [SystemMessageSubscriber::class, 'callEndedMessage']);
        $events->listen(DemotedAdminEvent::class, [SystemMessageSubscriber::class, 'demotedAdminMessage']);
        $events->listen(InviteUsedEvent::class, [SystemMessageSubscriber::class, 'joinedWithInviteMessage']);
        $events->listen(ParticipantsAddedEvent::class, [SystemMessageSubscriber::class, 'participantsAddedMessage']);
        $events->listen(PromotedAdminEvent::class, [SystemMessageSubscriber::class, 'promotedAdminMessage']);
        $events->listen(RemovedFromThreadEvent::class, [SystemMessageSubscriber::class, 'removedFromThreadMessage']);
        $events->listen(ThreadLeftEvent::class, [SystemMessageSubscriber::class, 'threadLeftMessage']);
        $events->listen(ThreadArchivedEvent::class, [SystemMessageSubscriber::class, 'threadArchivedMessage']);
        $events->listen(ThreadAvatarEvent::class, [SystemMessageSubscriber::class, 'threadAvatarMessage']);
        $events->listen(ThreadSettingsEvent::class, [SystemMessageSubscriber::class, 'threadNameMessage']);
    }

    /**
     * @param CallEndedEvent $event
     */
    public function callEndedMessage(CallEndedEvent $event): void
    {
        //
    }

    /**
     * @param DemotedAdminEvent $event
     */
    public function demotedAdminMessage(DemotedAdminEvent $event): void
    {
        //
    }

    /**
     * @param InviteUsedEvent $event
     */
    public function joinedWithInviteMessage(InviteUsedEvent $event): void
    {
        //
    }

    /**
     * @param ParticipantsAddedEvent $event
     */
    public function participantsAddedMessage(ParticipantsAddedEvent $event):  void
    {
        //
    }

    /**
     * @param PromotedAdminEvent $event
     */
    public function promotedAdminMessage(PromotedAdminEvent $event): void
    {
        //
    }

    /**
     * @param RemovedFromThreadEvent $event
     */
    public function removedFromThreadMessage(RemovedFromThreadEvent $event): void
    {
        //
    }

    /**
     * @param ThreadLeftEvent $event
     */
    public function threadLeftMessage(ThreadLeftEvent $event): void
    {
        //
    }

    /**
     * @param ThreadArchivedEvent $event
     */
    public function threadArchivedMessage(ThreadArchivedEvent $event): void
    {
        //
    }

    /**
     * @param ThreadAvatarEvent $event
     */
    public function threadAvatarMessage(ThreadAvatarEvent $event): void
    {
        //
    }

    /**
     * @param ThreadSettingsEvent $event
     */
    public function threadNameMessage(ThreadSettingsEvent $event): void
    {
        //
    }
}
