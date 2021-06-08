<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Http\Request\BotRequest;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;

class StoreBot extends BaseMessengerAction
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
     * @var Bot
     */
    private Bot $bot;

    /**
     * StoreBot constructor.
     *
     * @param Messenger $messenger
     * @param Dispatcher $dispatcher
     */
    public function __construct(Messenger $messenger, Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
    }

    /**
     * Create a new thread bot!
     *
     * @param mixed ...$parameters
     * @var Thread[0]
     * @var BotRequest[1]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->storeBot($parameters[1])
            ->generateResource()
            ->fireEvents();

        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    private function storeBot(array $params): self
    {
        $this->bot = $this->getThread()->bots()->create([
            'owner_id' => $this->messenger->getProvider()->getKey(),
            'owner_type' => $this->messenger->getProvider()->getMorphClass(),
            'enabled' => $params['enabled'],
            'name' => $params['name'],
            'cooldown' => $params['cooldown'],
            'avatar' => null,
        ])
        ->setRelations([
            'owner' => $this->messenger->getProvider(),
            'thread' => $this->getThread(),
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource($this->bot);

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
//        if ($this->shouldFireEvents()) {
//            $this->dispatcher->dispatch();
//        }
    }
}
