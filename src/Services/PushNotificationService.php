<?php

namespace RTippin\Messenger\Services;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use RTippin\Messenger\Contracts\BroadcastEvent;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\PushNotificationEvent;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Participant;

class PushNotificationService
{
    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * @var array
     */
    protected array $with = [];

    /**
     * @var Collection|null
     */
    protected ?Collection $recipients = null;

    /**
     * @var Application
     */
    protected Application $app;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * PushNotificationService constructor.
     *
     * @param Messenger $messenger
     * @param Dispatcher $dispatcher
     * @param Application $app
     */
    public function __construct(Messenger $messenger,
                                Dispatcher $dispatcher,
                                Application $app)
    {
        $this->messenger = $messenger;
        $this->app = $app;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Set recipients to the provided collection. Collection may
     * contain a mix of messenger providers, thread participants,
     * or call participants.
     *
     * @param Collection $recipients
     * @return $this
     */
    public function to(Collection $recipients): self
    {
        $this->recipients = $recipients;

        return $this;
    }

    /**
     * Set the resource we will use to broadcast out.
     *
     * @param array $resource
     * @return $this
     */
    public function with(array $resource): self
    {
        $this->with = $resource;

        return $this;
    }

    /**
     * We will use the abstract broadcast event to get the name of the notification,
     * then extract all providers from the given resource collection, and remove any
     * that do not have devices enabled, then fire the event with the formatted data
     * for our listener to handle on the queue.
     *
     * @param string|BroadcastEvent $abstract
     */
    public function notify(string $abstract): void
    {
        if (! is_null($this->recipients)
            && $this->recipients->count()) {
            $broadcastAs = $this->getBroadcastAs($abstract);

            $filteredRecipients = $this->extractValidProviders();

            if (! is_null($broadcastAs) && $filteredRecipients->count()) {
                $this->dispatchNotification($broadcastAs, $filteredRecipients);
            }
        }
    }

    /**
     * Construct the broadcast event to get the name defined.
     *
     * @param string $abstract
     * @return string|null
     */
    protected function getBroadcastAs(string $abstract): ?string
    {
        try {
            return $this->app
                ->make($abstract)
                ->broadcastAs();
        } catch (BindingResolutionException $e) {
            report($e);
            //continue on
        }

        return null;
    }

    /**
     * Get the end provider id/type from each resource, make sure devices
     * are enabled for that provider, and remove any duplicates.
     *
     * @return Collection
     */
    protected function extractValidProviders(): Collection
    {
        return $this->recipients
            ->map(fn ($recipient) => $this->extractProvider($recipient))
            ->reject(fn ($recipient) => ! count($recipient))
            ->reject(fn ($recipient) => ! in_array($recipient['owner_type'], $this->messenger->getAllProvidersWithDevices()))
            ->uniqueStrict('owner_id');
    }

    /**
     * @param mixed $recipient
     * @return array
     */
    protected function extractProvider($recipient): array
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

            return [
                'owner_type' => $recipient->owner_type,
                'owner_id' => $recipient->owner_id,
            ];
        }

        if (! in_array($abstract, $participants)
            && $this->messenger->isValidMessengerProvider($recipient)) {
            /** @var MessengerProvider $recipient */

            return [
                'owner_type' => get_class($recipient),
                'owner_id' => $recipient->getKey(),
            ];
        }

        return [];
    }

    /**
     * @param string $broadcastAs
     * @param Collection $recipients
     */
    protected function dispatchNotification(string $broadcastAs, Collection $recipients): void
    {
        $this->dispatcher->dispatch(new PushNotificationEvent(
            $broadcastAs,
            $this->with,
            $recipients
        ));
    }
}
