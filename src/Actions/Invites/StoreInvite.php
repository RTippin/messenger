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
use RTippin\Messenger\Models\Thread;

class StoreInvite extends InviteAction
{
    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * StoreInvite constructor.
     *
     * @param Messenger $messenger
     * @param Dispatcher $dispatcher
     */
    public function __construct(Messenger $messenger,
                                Dispatcher $dispatcher)
    {
        parent::__construct($messenger);

        $this->dispatcher = $dispatcher;
    }

    /**
     * Create a new thread invite!
     *
     * @param mixed ...$parameters
     * @var Thread[0]
     * @var InviteRequest[1]
     * @return $this
     * @throws FeatureDisabledException
     */
    public function execute(...$parameters): self
    {
        $this->isInvitationsEnabled();

        $this->setThread($parameters[0])
            ->storeInvite($parameters[1])
            ->generateResource()
            ->fireEvents();

        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    private function storeInvite(array $params): self
    {
        $this->setData(
            $this->getThread()
                ->invites()
                ->create([
                    'owner_id' => $this->messenger->getProviderId(),
                    'owner_type' => $this->messenger->getProviderClass(),
                    'code' => $this->generateInviteCode(),
                    'max_use' => $params['uses'],
                    'uses' => 0,
                    'expires_at' => $this->setExpiresAt($params['expires']),
                ])
                ->setRelations([
                    'owner' => $this->messenger->getProvider(),
                    'thread' => $this->getThread(),
                ])
        );

        return $this;
    }

    /**
     * @param int $option
     * @return Carbon|null
     */
    private function setExpiresAt(int $option): ?Carbon
    {
        switch ($option) {
            case 1:
                return now()->addMinutes(30);
            case 2:
                return now()->addHour();
            case 3:
                return now()->addHours(6);
            case 4:
                return now()->addHours(12);
            case 5:
                return now()->addDay();
            case 6:
                return now()->addWeek();
            case 7:
                return now()->addWeeks(2);
            case 8:
                return now()->addMonth();
            default:
                return null;
        }
    }

    /**
     * @return string
     */
    private function generateInviteCode(): string
    {
        return Str::upper(Str::random(8));
    }

    /**
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new InviteResource(
            $this->getData()
        ));

        return $this;
    }

    /**
     * Broadcast / fire events.
     *
     * @return $this
     */
    private function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new NewInviteEvent(
                $this->getData(true)
            ));
        }

        return $this;
    }
}
