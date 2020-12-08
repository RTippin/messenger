<?php

namespace RTippin\Messenger\Actions\Friends;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use RTippin\Messenger\Actions\Base\BaseMessengerAction;
use RTippin\Messenger\Events\FriendRemovedEvent;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Models\Friend;
use Throwable;

class RemoveFriend extends BaseMessengerAction
{
    /**
     * @var DatabaseManager
     */
    private DatabaseManager $database;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var Friend
     */
    private Friend $friend;

    /**
     * @var Friend|null
     */
    private ?Friend $inverseFriend;

    /**
     * RemoveFriend constructor.
     *
     * @param DatabaseManager $database
     * @param Dispatcher $dispatcher
     */
    public function __construct(DatabaseManager $database,
                                Dispatcher $dispatcher)
    {
        $this->database = $database;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Remove and destroy our friend relationship. This will
     * remove our friend model and its inverse/mirrored model.
     *
     * @param mixed ...$parameters
     * @var Friend[0]
     * @return $this
     * @throws Exception|Throwable
     */
    public function execute(...$parameters): self
    {
        $this->friend = $parameters[0];

        $this->getInverseFriend()
            ->handleTransactions()
            ->generateResource()
            ->fireEvents();

        return $this;
    }

    /**
     * @return $this
     * @throws Throwable
     */
    private function handleTransactions(): self
    {
        if ($this->isChained()) {
            $this->executeTransactions();
        } else {
            $this->database->transaction(
                fn () => $this->executeTransactions()
            );
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new ProviderResource(
            $this->friend->party
        ));

        return $this;
    }

    /**
     * @throws Exception
     */
    private function executeTransactions(): void
    {
        if (! is_null($this->inverseFriend)) {
            $this->inverseFriend->delete();
        }

        $this->friend->delete();
    }

    /**
     * @return $this
     */
    private function getInverseFriend(): self
    {
        $this->inverseFriend = Friend::where('owner_id', '=', $this->friend->party_id)
            ->where('owner_type', '=', $this->friend->party_type)
            ->where('party_id', '=', $this->friend->owner_id)
            ->where('party_type', '=', $this->friend->owner_type)
            ->first();

        return $this;
    }

    /**
     * @return $this
     */
    private function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new FriendRemovedEvent(
                $this->friend->withoutRelations(),
                $this->inverseFriend->withoutRelations()
            ));
        }

        return $this;
    }
}
