<?php

namespace RTippin\Messenger\Brokers;

use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Broadcasting\Factory;
use Illuminate\Support\Collection;
use RTippin\Messenger\Broadcasting\MessengerBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\ParticipantRepository;
use RTippin\Messenger\Services\PushNotificationService;
use RTippin\Messenger\Support\Helpers;
use Throwable;

class BroadcastBroker implements BroadcastDriver
{
    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * @var Thread|null
     */
    protected ?Thread $thread = null;

    /**
     * @var array
     */
    protected array $with = [];

    /**
     * @var Collection|null
     */
    protected ?Collection $recipients = null;

    /**
     * @var ParticipantRepository
     */
    protected ParticipantRepository $participantRepository;

    /**
     * @var Factory
     */
    protected Factory $broadcast;

    /**
     * @var bool
     */
    protected bool $usingPresence = false;

    /**
     * @var PushNotificationService
     */
    protected PushNotificationService $pushNotification;

    /**
     * BroadcastBroker constructor.
     *
     * @param Messenger $messenger
     * @param ParticipantRepository $participantRepository
     * @param PushNotificationService $pushNotification
     * @param Factory $broadcast
     */
    public function __construct(Messenger $messenger,
                                ParticipantRepository $participantRepository,
                                PushNotificationService $pushNotification,
                                Factory $broadcast)
    {
        $this->messenger = $messenger;
        $this->participantRepository = $participantRepository;
        $this->broadcast = $broadcast;
        $this->pushNotification = $pushNotification;
    }

    /**
     * @inheritDoc
     */
    public function toAllInThread(Thread $thread): self
    {
        $this->thread = $thread;
        $this->usingPresence = false;
        $this->recipients = $this->participantRepository->getThreadBroadcastableParticipants($this->thread);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toOthersInThread(Thread $thread): self
    {
        $this->thread = $thread;
        $this->usingPresence = false;
        $this->recipients = $this->participantRepository->getThreadBroadcastableParticipants($this->thread, true);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toSelected(Collection $recipients): self
    {
        $this->usingPresence = false;

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
        $this->usingPresence = false;
        $this->recipients = new Collection([$recipient]);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toPresence($entity): self
    {
        $this->usingPresence = true;
        $this->recipients = new Collection([$entity]);

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
            && Helpers::checkIsSubclassOf($abstract, MessengerBroadcast::class)) {
            if ($this->usingPresence) {
                $this->generatePresenceChannels()->each(fn (Collection $channels) => $this->executeBroadcast($abstract, $channels));
            } else {
                $this->generatePrivateChannels()->each(fn (Collection $channels) => $this->executeBroadcast($abstract, $channels));

                $this->executePushNotify($abstract);
            }
        }
    }

    /**
     * @return Collection
     */
    protected function generatePrivateChannels(): Collection
    {
        return $this->recipients
            ->map(fn ($recipient) => $this->generatePrivateChannel($recipient))
            ->reject(fn ($recipient) => is_null($recipient))
            ->chunk(100);
    }

    /**
     * Generate each private thread channel name. Accepts
     * thread and call participants, or messenger provider.
     *
     * outputs private-messenger.{alias}.{id}
     *
     * @param mixed $recipient
     * @return string|null
     */
    protected function generatePrivateChannel($recipient): ?string
    {
        $abstract = is_object($recipient)
            ? get_class($recipient)
            : '';

        $participants = [
            Participant::class,
            CallParticipant::class,
        ];

        if (in_array($abstract, $participants)
            && $this->messenger->isValidMessengerProvider($recipient->owner_type)) {
            /** @var Participant|CallParticipant $recipient */
            return "private-messenger.{$this->messenger->findProviderAlias($recipient->owner_type)}.$recipient->owner_id";
        }

        if (! in_array($abstract, $participants)
            && $this->messenger->isValidMessengerProvider($recipient)) {
            /** @var MessengerProvider $recipient */
            return "private-messenger.{$this->messenger->findProviderAlias($recipient)}.{$recipient->getKey()}";
        }

        return null;
    }

    /**
     * @return Collection
     */
    protected function generatePresenceChannels(): Collection
    {
        return $this->recipients
            ->map(fn ($recipient) => $this->generatePresenceChannel($recipient))
            ->reject(fn ($recipient) => is_null($recipient))
            ->chunk(100);
    }

    /**
     * @param $entity
     * @return string|null
     */
    protected function generatePresenceChannel($entity): ?string
    {
        $abstract = is_object($entity)
            ? get_class($entity)
            : null;

        if ($abstract === Thread::class) {
            /** @var Thread $entity */
            return "presence-messenger.thread.$entity->id";
        }

        if ($abstract === Call::class) {
            /** @var Call $entity */
            return "presence-messenger.call.$entity->id.thread.$entity->thread_id";
        }

        return null;
    }

    /**
     * @param string|MessengerBroadcast $abstractBroadcast
     * @param Collection $channels
     */
    protected function executeBroadcast(string $abstractBroadcast, Collection $channels): void
    {
        try {
            $this->broadcast->event(
                app($abstractBroadcast)
                    ->setResource($this->with)
                    ->setChannels($channels->values()->toArray())
            );
        } catch (BroadcastException | Throwable $e) {
            // Should a broadcast fail, we do not want to
            // halt further code execution. Continue on!
        }
    }

    /**
     * @param string $abstractBroadcast
     */
    protected function executePushNotify(string $abstractBroadcast): void
    {
        if ($this->messenger->isPushNotificationsEnabled()) {
            $this->pushNotification
                ->to($this->recipients)
                ->with($this->with)
                ->notify($abstractBroadcast);
        }
    }
}
