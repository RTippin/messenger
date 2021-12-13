<?php

namespace RTippin\Messenger\Actions\Invites;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RTippin\Messenger\Events\NewInviteEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Http\Request\InviteRequest;
use RTippin\Messenger\Http\Resources\InviteResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Thread;

class StoreInvite extends InviteAction
{
    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var Invite
     */
    private Invite $invite;

    /**
     * StoreInvite constructor.
     *
     * @param  Messenger  $messenger
     * @param  Dispatcher  $dispatcher
     */
    public function __construct(Messenger $messenger, Dispatcher $dispatcher)
    {
        parent::__construct($messenger);

        $this->dispatcher = $dispatcher;
    }

    /**
     * Create a new thread invite!
     *
     * @param  Thread  $thread
     * @param  array  $params
     * @return $this
     *
     * @see InviteRequest
     *
     * @throws FeatureDisabledException
     */
    public function execute(Thread $thread, array $params): self
    {
        $this->bailIfDisabled();

        $this->setThread($thread)
            ->storeInvite($params)
            ->generateResource()
            ->fireEvents();

        return $this;
    }

    /**
     * @param  array  $params
     * @return $this
     */
    private function storeInvite(array $params): self
    {
        $this->invite = $this->getThread()->invites()->create([
            'owner_id' => $this->messenger->getProvider()->getKey(),
            'owner_type' => $this->messenger->getProvider()->getMorphClass(),
            'code' => $this->generateInviteCode(),
            'max_use' => $params['uses'],
            'uses' => 0,
            'expires_at' => $this->setExpiresAt($params['expires'] ?? null),
        ])
        ->setRelations([
            'owner' => $this->messenger->getProvider(),
            'thread' => $this->getThread(),
        ]);

        return $this;
    }

    /**
     * @param  string|null  $expires
     * @return Carbon|null
     */
    private function setExpiresAt(?string $expires): ?Carbon
    {
        if (is_null($expires)) {
            return null;
        }

        return Carbon::parse($expires);
    }

    /**
     * @return string
     */
    private function generateInviteCode(): string
    {
        return Str::upper(Str::random(10));
    }

    /**
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new InviteResource(
            $this->invite
        ));

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new NewInviteEvent(
                $this->invite->withoutRelations()
            ));
        }
    }
}
