<?php

namespace RTippin\Messenger\Brokers;

use Illuminate\Contracts\Broadcasting\Factory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use RTippin\Messenger\Broadcasting\Channels\MessengerPresenceChannel;
use RTippin\Messenger\Broadcasting\Channels\MessengerPrivateChannel;
use RTippin\Messenger\Broadcasting\MessengerBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\HasPresenceChannel;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Ownerable;
use RTippin\Messenger\Events\BroadcastFailedEvent;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\ParticipantRepository;
use RTippin\Messenger\Services\PushNotificationService;
use Throwable;

class BroadcastBroker implements BroadcastDriver
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var ParticipantRepository
     */
    private ParticipantRepository $participantRepository;

    /**
     * @var Factory
     */
    private Factory $broadcast;

    /**
     * @var bool
     */
    private bool $usingPresence = false;

    /**
     * @var Thread|null
     */
    private ?Thread $thread = null;

    /**
     * @var array
     */
    private array $with = [];

    /**
     * @var Collection|null
     */
    private ?Collection $recipients = null;

    /**
     * @var PushNotificationService
     */
    private PushNotificationService $pushNotification;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * BroadcastBroker constructor.
     *
     * @param  Messenger  $messenger
     * @param  ParticipantRepository  $participantRepository
     * @param  PushNotificationService  $pushNotification
     * @param  Factory  $broadcast
     * @param  Dispatcher  $dispatcher
     */
    public function __construct(Messenger $messenger,
                                ParticipantRepository $participantRepository,
                                PushNotificationService $pushNotification,
                                Factory $broadcast,
                                Dispatcher $dispatcher)
    {
        $this->messenger = $messenger;
        $this->participantRepository = $participantRepository;
        $this->broadcast = $broadcast;
        $this->pushNotification = $pushNotification;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @inheritDoc
     */
    public function toAllInThread(Thread $thread): self
    {
        $this->thread = $thread;
        $this->recipients = $this->participantRepository->getThreadBroadcastableParticipants($this->thread);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toOthersInThread(Thread $thread): self
    {
        $this->thread = $thread;
        $this->recipients = $this->participantRepository->getThreadBroadcastableParticipants($this->thread, true);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toSelected(Collection $recipients): self
    {
        if ($recipients->count()) {
            $this->recipients = $recipients;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function to($recipient): self
    {
        $this->recipients = Collection::make([$recipient]);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toPresence($entity): self
    {
        $this->usingPresence = true;
        $this->recipients = Collection::make([$entity]);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toManyPresence(Collection $presence): self
    {
        $this->usingPresence = true;

        if ($presence->count()) {
            $this->recipients = $presence;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function with(array $with): self
    {
        $this->with = $with;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function broadcast(string $abstract): void
    {
        if (! is_null($this->recipients)
            && $this->recipients->count()
            && is_subclass_of($abstract, MessengerBroadcast::class)) {
            if ($this->usingPresence) {
                $this->generatePresenceChannels()->each(
                    fn (Collection $channels) => $this->executeBroadcast($abstract, $channels)
                );
            } else {
                $this->generatePrivateChannels()->each(
                    fn (Collection $channels) => $this->executeBroadcast($abstract, $channels)
                );

                $this->executePushNotify($abstract);
            }
        }

        $this->flush();
    }

    /**
     * @return Collection
     */
    private function generatePrivateChannels(): Collection
    {
        return $this->recipients
            ->map(fn ($recipient) => $this->generatePrivateChannel($recipient))
            ->filter()
            ->uniqueStrict()
            ->chunk(100);
    }

    /**
     * @param  Ownerable|MessengerProvider|mixed  $recipient
     * @return string|null
     */
    private function generatePrivateChannel($recipient): ?string
    {
        if ($recipient instanceof Ownerable
            || $recipient instanceof MessengerProvider) {
            return new MessengerPrivateChannel($recipient);
        }

        return null;
    }

    /**
     * @return Collection
     */
    private function generatePresenceChannels(): Collection
    {
        return $this->recipients
            ->map(fn ($recipient) => $this->generatePresenceChannel($recipient))
            ->filter()
            ->uniqueStrict()
            ->chunk(100);
    }

    /**
     * @param  HasPresenceChannel|mixed  $entity
     * @return string|null
     */
    private function generatePresenceChannel($entity): ?string
    {
        if ($entity instanceof HasPresenceChannel) {
            return new MessengerPresenceChannel($entity);
        }

        return null;
    }

    /**
     * @param  string|MessengerBroadcast  $abstractBroadcast
     * @param  Collection  $channels
     * @return void
     */
    private function executeBroadcast(string $abstractBroadcast, Collection $channels): void
    {
        try {
            $this->broadcast->event(
                app($abstractBroadcast)
                    ->setResource($this->with)
                    ->setChannels($channels->values()->toArray())
            );
        } catch (Throwable $e) {
            // Should a broadcast fail, code execution should not be
            // halted. Dispatch our failed event and continue on!
            $this->dispatcher->dispatch(new BroadcastFailedEvent(
                $abstractBroadcast,
                $channels->values()->toArray(),
                $this->with,
                $e
            ));
        }
    }

    /**
     * @param  string  $abstractBroadcast
     * @return void
     */
    private function executePushNotify(string $abstractBroadcast): void
    {
        if ($this->messenger->isPushNotificationsEnabled()) {
            $this->pushNotification
                ->to($this->recipients)
                ->with($this->with)
                ->notify($abstractBroadcast);
        }
    }

    /**
     * Reset our state.
     *
     * @return void
     */
    private function flush(): void
    {
        $this->usingPresence = false;
        $this->thread = null;
        $this->with = [];
        $this->recipients = null;
    }
}
