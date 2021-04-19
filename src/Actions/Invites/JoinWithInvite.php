<?php

namespace RTippin\Messenger\Actions\Invites;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use RTippin\Messenger\Actions\Threads\StoreParticipant;
use RTippin\Messenger\Events\InviteUsedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Invite;
use Throwable;

class JoinWithInvite extends InviteAction
{
    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var DatabaseManager
     */
    private DatabaseManager $database;

    /**
     * @var Invite
     */
    private Invite $invite;

    /**
     * JoinWithInvite constructor.
     *
     * @param Messenger $messenger
     * @param DatabaseManager $database
     * @param Dispatcher $dispatcher
     */
    public function __construct(Messenger $messenger,
                                DatabaseManager $database,
                                Dispatcher $dispatcher)
    {
        parent::__construct($messenger);

        $this->dispatcher = $dispatcher;
        $this->database = $database;
    }

    /**
     * @param mixed ...$parameters
     * @var Invite[0]
     * @return $this
     * @throws Exception|Throwable|FeatureDisabledException
     */
    public function execute(...$parameters): self
    {
        $this->isInvitationsEnabled();

        $this->invite = $parameters[0];

        $this->setThread($this->invite->thread)
            ->handleTransactions()
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
            $this->database->transaction(fn () => $this->executeTransactions(), 3);
        }

        return $this;
    }

    /**
     * Execute all actions that must occur for
     * a successful private thread creation.
     *
     * @return  void
     */
    private function executeTransactions(): void
    {
        $this->incrementInviteUses();

        $this->setParticipant(
            $this->chain(StoreParticipant::class)
                ->execute(...$this->addParticipant())
                ->getParticipant()
        );
    }

    /**
     * @return void
     */
    private function incrementInviteUses(): void
    {
        $this->invite->increment('uses');
    }

    /**
     * Execute params for self participant.
     *
     * @mixin StoreParticipant
     * @return array
     */
    private function addParticipant(): array
    {
        return [
            $this->getThread(),
            $this->messenger->getProvider(),
            [],
            true,
        ];
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new InviteUsedEvent(
                $this->messenger->getProvider()->withoutRelations(),
                $this->getThread(true),
                $this->invite->withoutRelations()
            ));
        }
    }
}
