<?php

namespace RTippin\Messenger\Actions\Messages;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\MessageEditedBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\EmojiInterface;
use RTippin\Messenger\Events\MessageEditedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Http\Resources\MessageResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use Throwable;

class EditMessage extends BaseMessengerAction
{
    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * @var DatabaseManager
     */
    private DatabaseManager $database;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var EmojiInterface
     */
    private EmojiInterface $emoji;

    /**
     * @var string|null
     */
    private ?string $originalBody;

    /**
     * EditMessage constructor.
     *
     * @param  BroadcastDriver  $broadcaster
     * @param  DatabaseManager  $database
     * @param  Dispatcher  $dispatcher
     * @param  Messenger  $messenger
     * @param  EmojiInterface  $emoji
     */
    public function __construct(BroadcastDriver $broadcaster,
                                DatabaseManager $database,
                                Dispatcher $dispatcher,
                                Messenger $messenger,
                                EmojiInterface $emoji)
    {
        $this->broadcaster = $broadcaster;
        $this->database = $database;
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
        $this->emoji = $emoji;
    }

    /**
     * Update the given message.
     *
     * @param  Thread  $thread
     * @param  Message  $message
     * @param  string  $newBody
     * @return $this
     *
     * @throws FeatureDisabledException|Throwable
     */
    public function execute(Thread $thread,
                            Message $message,
                            string $newBody): self
    {
        $this->bailWhenFeatureDisabled();

        $this->setThread($thread)
            ->setMessage($message)
            ->process($newBody)
            ->generateResource();

        if ($this->getMessage()->wasChanged()) {
            Cache::forget(Message::getReplyMessageCacheKey($this->getMessage()->id));

            $this->fireBroadcast()->fireEvents();
        }

        return $this;
    }

    /**
     * @throws FeatureDisabledException
     */
    private function bailWhenFeatureDisabled(): void
    {
        if (! $this->messenger->isMessageEditsEnabled()) {
            throw new FeatureDisabledException('Edit messages are currently disabled.');
        }
    }

    /**
     * @param  string  $body
     * @return $this
     *
     * @throws Throwable
     */
    private function process(string $body): self
    {
        $this->isChained()
            ? $this->handle($body)
            : $this->database->transaction(fn () => $this->handle($body));

        return $this;
    }

    /**
     * @param  string  $body
     * @return $this
     */
    private function handle(string $body): self
    {
        $newBody = $this->emoji->toShort($body);

        if ($this->getMessage()->body !== $newBody) {
            $this->originalBody = $this->getMessage()->body;

            $this->getMessage()->update([
                'body' => $newBody,
                'edited' => true,
            ]);

            $this->getMessage()->edits()->create([
                'body' => $this->originalBody,
                'edited_at' => $this->getMessage()->updated_at,
            ]);
        }

        return $this;
    }

    /**
     * @return void
     */
    private function generateResource(): void
    {
        $this->setJsonResource(new MessageResource(
                $this->getMessage(),
                $this->getThread(),
                true
            )
        );
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
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new MessageEditedEvent(
                $this->getMessage(true),
                $this->originalBody
            ));
        }
    }
}
