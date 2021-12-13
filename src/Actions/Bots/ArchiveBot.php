<?php

namespace RTippin\Messenger\Actions\Bots;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Events\BotArchivedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;

class ArchiveBot extends BaseMessengerAction
{
    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * ArchiveBot constructor.
     *
     * @param  Messenger  $messenger
     * @param  Dispatcher  $dispatcher
     */
    public function __construct(Messenger $messenger, Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
    }

    /**
     * @param  Bot  $bot
     * @return $this
     *
     * @throws Exception|FeatureDisabledException
     */
    public function execute(Bot $bot): self
    {
        $this->bailIfDisabled();

        $this->setBot($bot)
            ->archiveBot()
            ->clearActionsCache()
            ->fireEvents();

        return $this;
    }

    /**
     * @throws FeatureDisabledException
     */
    private function bailIfDisabled(): void
    {
        if (! $this->messenger->isBotsEnabled()) {
            throw new FeatureDisabledException('Bots are currently disabled.');
        }
    }

    /**
     * @return $this
     *
     * @throws Exception
     */
    private function archiveBot(): self
    {
        $this->getBot()->delete();

        return $this;
    }

    /**
     * @return $this
     */
    private function clearActionsCache(): self
    {
        BotAction::clearActionsCacheForThread($this->getBot()->thread_id);

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new BotArchivedEvent(
                $this->messenger->getProvider(true),
                $this->getBot(true)
            ));
        }
    }
}
