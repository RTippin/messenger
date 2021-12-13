<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Events\Dispatcher;
use RTippin\Messenger\Events\BotArchivedEvent;
use RTippin\Messenger\Events\BotAvatarEvent;
use RTippin\Messenger\Events\BotUpdatedEvent;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Events\DemotedAdminEvent;
use RTippin\Messenger\Events\InviteUsedEvent;
use RTippin\Messenger\Events\NewBotEvent;
use RTippin\Messenger\Events\PackagedBotInstalledEvent;
use RTippin\Messenger\Events\ParticipantsAddedEvent;
use RTippin\Messenger\Events\PromotedAdminEvent;
use RTippin\Messenger\Events\RemovedFromThreadEvent;
use RTippin\Messenger\Events\ThreadArchivedEvent;
use RTippin\Messenger\Events\ThreadAvatarEvent;
use RTippin\Messenger\Events\ThreadLeftEvent;
use RTippin\Messenger\Events\ThreadSettingsEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\BotAddedMessage;
use RTippin\Messenger\Jobs\BotAvatarMessage;
use RTippin\Messenger\Jobs\BotInstalledMessage;
use RTippin\Messenger\Jobs\BotNameMessage;
use RTippin\Messenger\Jobs\BotRemovedMessage;
use RTippin\Messenger\Jobs\CallEndedMessage;
use RTippin\Messenger\Jobs\DemotedAdminMessage;
use RTippin\Messenger\Jobs\JoinedWithInviteMessage;
use RTippin\Messenger\Jobs\ParticipantsAddedMessage;
use RTippin\Messenger\Jobs\PromotedAdminMessage;
use RTippin\Messenger\Jobs\RemovedFromThreadMessage;
use RTippin\Messenger\Jobs\ThreadArchivedMessage;
use RTippin\Messenger\Jobs\ThreadAvatarMessage;
use RTippin\Messenger\Jobs\ThreadLeftMessage;
use RTippin\Messenger\Jobs\ThreadNameMessage;

class SystemMessageSubscriber
{
    /**
     * Register the listeners for the subscriber.
     *
     * @param  Dispatcher  $events
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
        $events->listen(ThreadArchivedEvent::class, [SystemMessageSubscriber::class, 'threadArchivedMessage']);
        $events->listen(ThreadAvatarEvent::class, [SystemMessageSubscriber::class, 'threadAvatarMessage']);
        $events->listen(ThreadLeftEvent::class, [SystemMessageSubscriber::class, 'threadLeftMessage']);
        $events->listen(ThreadSettingsEvent::class, [SystemMessageSubscriber::class, 'threadNameMessage']);
        $events->listen(NewBotEvent::class, [SystemMessageSubscriber::class, 'botAddedMessage']);
        $events->listen(BotUpdatedEvent::class, [SystemMessageSubscriber::class, 'botNameMessage']);
        $events->listen(BotAvatarEvent::class, [SystemMessageSubscriber::class, 'botAvatarMessage']);
        $events->listen(BotArchivedEvent::class, [SystemMessageSubscriber::class, 'botRemovedMessage']);
        $events->listen(PackagedBotInstalledEvent::class, [SystemMessageSubscriber::class, 'botInstalledMessage']);
    }

    /**
     * @param  CallEndedEvent  $event
     * @return void
     */
    public function callEndedMessage(CallEndedEvent $event): void
    {
        if ($this->isEnabled()) {
            $this->shouldQueue()
                ? CallEndedMessage::dispatch($event)->onQueue(Messenger::getSystemMessageSubscriber('channel'))
                : CallEndedMessage::dispatchSync($event);
        }
    }

    /**
     * @param  DemotedAdminEvent  $event
     * @return void
     */
    public function demotedAdminMessage(DemotedAdminEvent $event): void
    {
        if ($this->isEnabled()) {
            $this->shouldQueue()
                ? DemotedAdminMessage::dispatch($event)->onQueue(Messenger::getSystemMessageSubscriber('channel'))
                : DemotedAdminMessage::dispatchSync($event);
        }
    }

    /**
     * @param  InviteUsedEvent  $event
     * @return void
     */
    public function joinedWithInviteMessage(InviteUsedEvent $event): void
    {
        if ($this->isEnabled()) {
            $this->shouldQueue()
                ? JoinedWithInviteMessage::dispatch($event)->onQueue(Messenger::getSystemMessageSubscriber('channel'))
                : JoinedWithInviteMessage::dispatchSync($event);
        }
    }

    /**
     * @param  ParticipantsAddedEvent  $event
     * @return void
     */
    public function participantsAddedMessage(ParticipantsAddedEvent $event): void
    {
        if ($this->isEnabled()) {
            $this->shouldQueue()
                ? ParticipantsAddedMessage::dispatch($event)->onQueue(Messenger::getSystemMessageSubscriber('channel'))
                : ParticipantsAddedMessage::dispatchSync($event);
        }
    }

    /**
     * @param  PromotedAdminEvent  $event
     * @return void
     */
    public function promotedAdminMessage(PromotedAdminEvent $event): void
    {
        if ($this->isEnabled()) {
            $this->shouldQueue()
                ? PromotedAdminMessage::dispatch($event)->onQueue(Messenger::getSystemMessageSubscriber('channel'))
                : PromotedAdminMessage::dispatchSync($event);
        }
    }

    /**
     * @param  RemovedFromThreadEvent  $event
     * @return void
     */
    public function removedFromThreadMessage(RemovedFromThreadEvent $event): void
    {
        if ($this->isEnabled()) {
            $this->shouldQueue()
                ? RemovedFromThreadMessage::dispatch($event)->onQueue(Messenger::getSystemMessageSubscriber('channel'))
                : RemovedFromThreadMessage::dispatchSync($event);
        }
    }

    /**
     * @param  ThreadArchivedEvent  $event
     * @return void
     */
    public function threadArchivedMessage(ThreadArchivedEvent $event): void
    {
        if ($this->isEnabled()) {
            $this->shouldQueue()
                ? ThreadArchivedMessage::dispatch($event)->onQueue(Messenger::getSystemMessageSubscriber('channel'))
                : ThreadArchivedMessage::dispatchSync($event);
        }
    }

    /**
     * @param  ThreadAvatarEvent  $event
     * @return void
     */
    public function threadAvatarMessage(ThreadAvatarEvent $event): void
    {
        if ($this->isEnabled()) {
            $this->shouldQueue()
                ? ThreadAvatarMessage::dispatch($event)->onQueue(Messenger::getSystemMessageSubscriber('channel'))
                : ThreadAvatarMessage::dispatchSync($event);
        }
    }

    /**
     * @param  ThreadLeftEvent  $event
     * @return void
     */
    public function threadLeftMessage(ThreadLeftEvent $event): void
    {
        if ($this->isEnabled()) {
            $this->shouldQueue()
                ? ThreadLeftMessage::dispatch($event)->onQueue(Messenger::getSystemMessageSubscriber('channel'))
                : ThreadLeftMessage::dispatchSync($event);
        }
    }

    /**
     * @param  ThreadSettingsEvent  $event
     * @return void
     */
    public function threadNameMessage(ThreadSettingsEvent $event): void
    {
        if ($this->isEnabled() && $event->nameChanged) {
            $this->shouldQueue()
                ? ThreadNameMessage::dispatch($event)->onQueue(Messenger::getSystemMessageSubscriber('channel'))
                : ThreadNameMessage::dispatchSync($event);
        }
    }

    /**
     * @param  NewBotEvent  $event
     * @return void
     */
    public function botAddedMessage(NewBotEvent $event): void
    {
        if ($this->isEnabled()) {
            $this->shouldQueue()
                ? BotAddedMessage::dispatch($event)->onQueue(Messenger::getSystemMessageSubscriber('channel'))
                : BotAddedMessage::dispatchSync($event);
        }
    }

    /**
     * @param  BotUpdatedEvent  $event
     * @return void
     */
    public function botNameMessage(BotUpdatedEvent $event): void
    {
        if ($this->isEnabled() && $event->originalName !== $event->bot->name) {
            $this->shouldQueue()
                ? BotNameMessage::dispatch($event)->onQueue(Messenger::getSystemMessageSubscriber('channel'))
                : BotNameMessage::dispatchSync($event);
        }
    }

    /**
     * @param  BotAvatarEvent  $event
     * @return void
     */
    public function botAvatarMessage(BotAvatarEvent $event): void
    {
        if ($this->isEnabled()) {
            $this->shouldQueue()
                ? BotAvatarMessage::dispatch($event)->onQueue(Messenger::getSystemMessageSubscriber('channel'))
                : BotAvatarMessage::dispatchSync($event);
        }
    }

    /**
     * @param  BotArchivedEvent  $event
     * @return void
     */
    public function botRemovedMessage(BotArchivedEvent $event): void
    {
        if ($this->isEnabled()) {
            $this->shouldQueue()
                ? BotRemovedMessage::dispatch($event)->onQueue(Messenger::getSystemMessageSubscriber('channel'))
                : BotRemovedMessage::dispatchSync($event);
        }
    }

    /**
     * @param  PackagedBotInstalledEvent  $event
     * @return void
     */
    public function botInstalledMessage(PackagedBotInstalledEvent $event): void
    {
        if ($this->isEnabled()) {
            $this->shouldQueue()
                ? BotInstalledMessage::dispatch($event)->onQueue(Messenger::getSystemMessageSubscriber('channel'))
                : BotInstalledMessage::dispatchSync($event);
        }
    }

    /**
     * @return bool
     */
    private function isEnabled(): bool
    {
        return Messenger::getSystemMessageSubscriber('enabled');
    }

    /**
     * @return bool
     */
    private function shouldQueue(): bool
    {
        return Messenger::getSystemMessageSubscriber('queued');
    }
}
