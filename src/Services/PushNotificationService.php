<?php

namespace RTippin\Messenger\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use RTippin\Messenger\Broadcasting\MessengerBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Ownerable;
use RTippin\Messenger\Events\PushNotificationEvent;
use RTippin\Messenger\Messenger;
use Throwable;

class PushNotificationService
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var array
     */
    private array $with = [];

    /**
     * @var Collection|null
     */
    private ?Collection $recipients = null;

    /**
     * PushNotificationService constructor.
     *
     * @param  Messenger  $messenger
     * @param  Dispatcher  $dispatcher
     */
    public function __construct(Messenger $messenger, Dispatcher $dispatcher)
    {
        $this->messenger = $messenger;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Set recipients to the provided collection. Collection may
     * contain a mix of messenger providers and any of our
     * internal models that implement Ownerable.
     *
     * @param  Collection  $recipients
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
     * @param  array  $resource
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
     * @param  string|MessengerBroadcast  $abstract
     * @return void
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

        $this->flush();
    }

    /**
     * Construct the broadcast event to get the name defined.
     *
     * @param  string  $abstract
     * @return string|null
     */
    private function getBroadcastAs(string $abstract): ?string
    {
        try {
            return app($abstract)->broadcastAs();
        } catch (Throwable $e) {
            // Should constructing a notification fail, we do not
            // want to halt further code execution. Continue on!
        }

        return null;
    }

    /**
     * Get the end provider id/type from each resource, make sure devices
     * are enabled for that provider, and remove any duplicates.
     *
     * @return Collection
     */
    private function extractValidProviders(): Collection
    {
        return $this->recipients
            ->map(fn ($recipient) => $this->extractProvider($recipient))
            ->filter()
            ->reject(fn ($recipient) => ! in_array($recipient['owner_type'], $this->messenger->getAllProvidersWithDevices()))
            ->uniqueStrict(fn ($recipient) => $recipient['owner_type'].$recipient['owner_id'])
            ->values();
    }

    /**
     * @param  MessengerProvider|Ownerable|mixed  $recipient
     * @return array|null
     */
    private function extractProvider($recipient): ?array
    {
        if ($recipient instanceof Ownerable
            && $this->messenger->isValidMessengerProvider($recipient->owner_type)) {
            return [
                'owner_type' => $recipient->owner_type,
                'owner_id' => $recipient->owner_id,
            ];
        }

        if ($recipient instanceof MessengerProvider) {
            return [
                'owner_type' => $recipient->getMorphClass(),
                'owner_id' => $recipient->getKey(),
            ];
        }

        return null;
    }

    /**
     * @param  string  $broadcastAs
     * @param  Collection  $recipients
     * @return void
     */
    private function dispatchNotification(string $broadcastAs, Collection $recipients): void
    {
        $this->dispatcher->dispatch(new PushNotificationEvent(
            $broadcastAs,
            $this->with,
            $recipients
        ));
    }

    /**
     * Reset our state.
     *
     * @return void
     */
    private function flush(): void
    {
        $this->recipients = null;
        $this->with = [];
    }
}
