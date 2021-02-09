<?php

namespace RTippin\Messenger\Actions\Messages;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\MessageEditedBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\MessageEditedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Http\Resources\MessageResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\EmojiConverter;

class UpdateMessage extends BaseMessengerAction
{
    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var EmojiConverter
     */
    private EmojiConverter $converter;

    /**
     * @var string
     */
    private string $originalBody;

    /**
     * StoreMessage constructor.
     *
     * @param BroadcastDriver $broadcaster
     * @param Dispatcher $dispatcher
     * @param Messenger $messenger
     * @param EmojiConverter $converter
     */
    public function __construct(BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher,
                                Messenger $messenger,
                                EmojiConverter $converter)
    {
        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
        $this->converter = $converter;
    }

    /**
     * Update the given message.
     *
     * @param mixed ...$parameters
     * @var Thread[0]
     * @var Message[1]
     * @var string[2]
     * @return $this
     * @throws FeatureDisabledException
     */
    public function execute(...$parameters): self
    {
        $this->isEditMessagesEnabled();

        $this->setThread($parameters[0])
            ->setMessage($parameters[1])
            ->updateMessage($parameters[2])
            ->generateResource();

        if ($this->getMessage()->wasChanged()) {
            $this->fireBroadcast()->fireEvents();
        }

        return $this;
    }

    /**
     * @throws FeatureDisabledException
     */
    private function isEditMessagesEnabled(): void
    {
        if (! $this->messenger->isMessageEditsEnabled()) {
            throw new FeatureDisabledException('Edit messages are currently disabled.');
        }
    }

    /**
     * @param string $body
     * @return $this
     */
    private function updateMessage(string $body): self
    {
        $this->originalBody = $this->getMessage()->body;

        $this->getMessage()->update([
            'body' => $this->converter->toShort($body),
        ]);

        return $this;
    }

    /**
     * Generate the message resource.
     *
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new MessageResource(
                $this->getMessage(), $this->getThread()
            )
        );

        return $this;
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->toPresence($this->getThread())
                ->with($this->getJsonResource()->resolve())
                ->broadcast(MessageEditedBroadcast::class);
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new MessageEditedEvent(
                $this->getMessage(true),
                $this->originalBody
            ));
        }

        return $this;
    }
}
