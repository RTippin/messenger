<?php

namespace RTippin\Messenger;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\PrivateThreadRepository;
use RTippin\Messenger\Repositories\ProvidersRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RecipientThreadLocator
{
    /**
     * @var PrivateThreadRepository
     */
    private PrivateThreadRepository $privateThreadRepository;

    /**
     * @var ProvidersRepository
     */
    private ProvidersRepository $providersRepository;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var string|null
     */
    private ?string $alias = null;

    /**
     * @var string|null
     */
    private ?string $id = null;

    /**
     * @var MessengerProvider|null
     */
    private ?MessengerProvider $recipient = null;

    /**
     * @var Thread|null
     */
    private ?Thread $thread = null;

    /**
     * RecipientThreadLocator constructor.
     *
     * @param Messenger $messenger
     * @param ProvidersRepository $providersRepository
     * @param PrivateThreadRepository $privateThreadRepository
     */
    public function __construct(Messenger $messenger,
                                ProvidersRepository $providersRepository,
                                PrivateThreadRepository $privateThreadRepository)
    {
        $this->privateThreadRepository = $privateThreadRepository;
        $this->providersRepository = $providersRepository;
        $this->messenger = $messenger;
    }

    /**
     * @param string $alias
     * @return $this
     */
    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Perform the lookup and set recipient/thread.
     */
    public function locate(): self
    {
        $this->locateRecipient()
            ->locateThread();

        return $this;
    }

    /**
     * @return MessengerProvider|null
     */
    public function getRecipient(): ?MessengerProvider
    {
        return $this->recipient;
    }

    /**
     * @return Thread|null
     */
    public function getThread(): ?Thread
    {
        return $this->thread;
    }

    /**
     * @throws NotFoundHttpException
     */
    public function throwNotFoundError()
    {
        throw new NotFoundHttpException('We were unable to locate the recipient you requested.');
    }

    /**
     * @noinspection PhpParamsInspection
     */
    private function locateRecipient(): self
    {
        /** @var MessengerProvider|null $recipient */
        $recipient = $this->providersRepository
            ->getProviderUsingAliasAndId(
                $this->alias,
                $this->id
            );

        if ($recipient
            && $recipient->isNot($this->messenger->getProvider())) {
            $this->recipient = $recipient;
        }

        return $this;
    }

    /**
     * Locate private thread current provider has with recipient.
     *
     * @return $this
     */
    private function locateThread(): self
    {
        $this->thread = $this->privateThreadRepository
            ->getProviderPrivateThreadWithRecipient($this->recipient);

        return $this;
    }
}
