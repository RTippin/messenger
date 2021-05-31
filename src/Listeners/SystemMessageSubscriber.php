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
use RTippin\Messenger\Messenger;

class SystemMessageSubscriber
{
    /**
     * @var bool|string
     */
    private bool $isEnabled;

    /**
     * @var bool|string
     */
    private bool $isQueued;

    /**
     * @var string|bool
     */
    private string $channel;

    /**
     * SystemMessageSubscriber constructor.
     */
    public function __construct(Messenger $messenger)
    {
        $this->isEnabled = $messenger->getSystemMessagesSubscriber('enabled');
        $this->isQueued = $messenger->getSystemMessagesSubscriber('queued');
        $this->channel = $messenger->getSystemMessagesSubscriber('channel');
    }

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
        $events->listen(ThreadArchivedEvent::class, [SystemMessageSubscriber::class, 'threadArchivedMessage']);
        $events->listen(ThreadAvatarEvent::class, [SystemMessageSubscriber::class, 'threadAvatarMessage']);
        $events->listen(ThreadLeftEvent::class, [SystemMessageSubscriber::class, 'threadLeftMessage']);
        $events->listen(ThreadSettingsEvent::class, [SystemMessageSubscriber::class, 'threadNameMessage']);
    }

    /**
     * @param CallEndedEvent $event
     */
    public function callEndedMessage(CallEndedEvent $event): void
    {
        if ($this->isEnabled) {
            $this->isQueued
                ? CallEndedMessage::dispatch($event)->onQueue($this->channel)
                : CallEndedMessage::dispatchSync($event);
        }
    }

    /**
     * @param DemotedAdminEvent $event
     */
    public function demotedAdminMessage(DemotedAdminEvent $event): void
    {
        if ($this->isEnabled) {
            $this->isQueued
                ? DemotedAdminMessage::dispatch($event)->onQueue($this->channel)
                : DemotedAdminMessage::dispatchSync($event);
        }
    }

    /**
     * @param InviteUsedEvent $event
     */
    public function joinedWithInviteMessage(InviteUsedEvent $event): void
    {
        if ($this->isEnabled) {
            $this->isQueued
                ? JoinedWithInviteMessage::dispatch($event)->onQueue($this->channel)
                : JoinedWithInviteMessage::dispatchSync($event);
        }
    }

    /**
     * @param ParticipantsAddedEvent $event
     */
    public function participantsAddedMessage(ParticipantsAddedEvent $event): void
    {
        if ($this->isEnabled) {
            $this->isQueued
                ? ParticipantsAddedMessage::dispatch($event)->onQueue($this->channel)
                : ParticipantsAddedMessage::dispatchSync($event);
        }
    }

    /**
     * @param PromotedAdminEvent $event
     */
    public function promotedAdminMessage(PromotedAdminEvent $event): void
    {
        if ($this->isEnabled) {
            $this->isQueued
                ? PromotedAdminMessage::dispatch($event)->onQueue($this->channel)
                : PromotedAdminMessage::dispatchSync($event);
        }
    }

    /**
     * @param RemovedFromThreadEvent $event
     */
    public function removedFromThreadMessage(RemovedFromThreadEvent $event): void
    {
        if ($this->isEnabled) {
            $this->isQueued
                ? RemovedFromThreadMessage::dispatch($event)->onQueue($this->channel)
                : RemovedFromThreadMessage::dispatchSync($event);
        }
    }

    /**
     * @param ThreadArchivedEvent $event
     */
    public function threadArchivedMessage(ThreadArchivedEvent $event): void
    {
        if ($this->isEnabled) {
            $this->isQueued
                ? ThreadArchivedMessage::dispatch($event)->onQueue($this->channel)
                : ThreadArchivedMessage::dispatchSync($event);
        }
    }

    /**
     * @param ThreadAvatarEvent $event
     */
    public function threadAvatarMessage(ThreadAvatarEvent $event): void
    {
        if ($this->isEnabled) {
            $this->isQueued
                ? ThreadAvatarMessage::dispatch($event)->onQueue($this->channel)
                : ThreadAvatarMessage::dispatchSync($event);
        }
    }

    /**
     * @param ThreadLeftEvent $event
     */
    public function threadLeftMessage(ThreadLeftEvent $event): void
    {
        if ($this->isEnabled) {
            $this->isQueued
                ? ThreadLeftMessage::dispatch($event)->onQueue($this->channel)
                : ThreadLeftMessage::dispatchSync($event);
        }
    }

    /**
     * @param ThreadSettingsEvent $event
     */
    public function threadNameMessage(ThreadSettingsEvent $event): void
    {
        if ($this->isEnabled && $event->nameChanged) {
            $this->isQueued
                ? ThreadNameMessage::dispatch($event)->onQueue($this->channel)
                : ThreadNameMessage::dispatchSync($event);
        }
    }
}
