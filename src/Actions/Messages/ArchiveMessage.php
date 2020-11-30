<?php

namespace RTippin\Messenger\Actions\Messages;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\Base\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\MessageArchivedBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\MessageArchivedEvent;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;

class ArchiveMessage extends BaseMessengerAction
{
    /**
     * @var BroadcastDriver
     */
    protected BroadcastDriver $broadcaster;

    /**
     * @var Dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * ArchiveMessage constructor.
     *
     * @param Messenger $messenger
     * @param BroadcastDriver $broadcaster
     * @param Dispatcher $dispatcher
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher)
    {

        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
    }

    /**
     * Archive the message
     *
     * @param mixed ...$parameters
     * @return $this
     * @var Thread $thread $parameters[0]
     * @var Message $message $parameters[1]
     * @throws Exception
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->setMessage($parameters[1])
            ->archiveMessage()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    private function archiveMessage(): self
    {
        $this->getMessage()->delete();

        return $this;
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if($this->shouldFireBroadcast())
        {
            $this->broadcaster
                ->toAllInThread($this->getThread())
                ->with($this->generateBroadcastResource())
                ->broadcast(MessageArchivedBroadcast::class);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function fireEvents(): self
    {
        if($this->shouldFireEvents())
        {
            $this->dispatcher->dispatch(new MessageArchivedEvent(
                $this->messenger->getProvider()->withoutRelations(),
                $this->getMessage(true)
            ));
        }

        return $this;
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return [
            'message_id' => $this->getMessage()->id,
            'thread_id' => $this->getMessage()->thread_id
        ];
    }
}