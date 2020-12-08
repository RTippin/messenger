<?php

namespace RTippin\Messenger\Actions\Base;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;
use ReflectionClass;
use ReflectionException;
use RTippin\Messenger\Contracts\Action;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;

abstract class BaseMessengerAction implements Action
{
    /**
     * @var null|string|Model|mixed|Collection
     */
    private $data = null;

    /**
     * @var null|JsonResource|mixed
     */
    private $jsonResource = null;

    /**
     * @var null|JsonResponse|mixed
     */
    private $messageResponse = null;

    /**
     * @var Thread|null
     */
    private ?Thread $thread = null;

    /**
     * @var Message|null
     */
    private ?Message $message = null;

    /**
     * @var Participant|null
     */
    private ?Participant $participant = null;

    /**
     * @var CallParticipant|null
     */
    private ?CallParticipant $callParticipant = null;

    /**
     * @var Call|null
     */
    private ?Call $call = null;

    /**
     * @var bool
     */
    private bool $shouldFireEvents = true;

    /**
     * @var bool
     */
    private bool $shouldFireBroadcast = true;

    /**
     * @var bool
     */
    private bool $shouldExecuteChains = true;

    /**
     * @var bool
     */
    private bool $isChained = false;

    /**
     * @inheritDoc
     */
    abstract public function execute(...$parameters);

    /**
     * @inheritDoc
     */
    public function isChained(): bool
    {
        return $this->isChained;
    }

    /**
     * @inheritDoc
     */
    public function shouldExecuteChains(): bool
    {
        return $this->shouldExecuteChains;
    }

    /**
     * @inheritDoc
     */
    public function shouldFireEvents(): bool
    {
        return $this->shouldFireEvents;
    }

    /**
     * @inheritDoc
     */
    public function shouldFireBroadcast(): bool
    {
        return $this->shouldFireBroadcast;
    }

    /**
     * @inheritDoc
     */
    public function chain(string $abstractAction): Action
    {
        try {
            if ((new ReflectionClass($abstractAction))
                ->isSubclassOf(Action::class)) {
                return app($abstractAction)->continuesChain();
            }
        } catch (ReflectionException $e) {
            //skip
        }

        throw new LogicException('Invalid chained action.');
    }

    /**
     * @inheritDoc
     */
    public function continuesChain(): self
    {
        $this->isChained = true;
        $this->shouldExecuteChains = false;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getJsonResource()
    {
        return ! is_null($this->jsonResource)
            ? $this->jsonResource
            : new JsonResponse([]);
    }

    /**
     * @inheritDoc
     */
    public function setJsonResource($resource): self
    {
        $this->jsonResource = $resource;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMessageResponse()
    {
        if (! is_null($this->messageResponse)) {
            return $this->messageResponse;
        }

        return new JsonResponse([
            'message' => 'success',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function setMessageResponse($messageResponse): self
    {
        $this->messageResponse = $messageResponse;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getData($withoutRelations = false)
    {
        if ($withoutRelations
            && ! is_null($this->data)) {
            return $this->data->withoutRelations();
        }

        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getThread($withoutRelations = false): ?Thread
    {
        if ($withoutRelations
            && ! is_null($this->thread)) {
            return $this->thread->withoutRelations();
        }

        return $this->thread;
    }

    /**
     * @inheritDoc
     */
    public function setThread(Thread $thread = null): self
    {
        if (! is_null($thread)) {
            $this->thread = $thread;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getParticipant($withoutRelations = false): ?Participant
    {
        if ($withoutRelations
            && ! is_null($this->participant)) {
            return $this->participant->withoutRelations();
        }

        return $this->participant;
    }

    /**
     * @inheritDoc
     */
    public function setParticipant(Participant $participant = null): self
    {
        if (! is_null($participant)) {
            $this->participant = $participant;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCallParticipant($withoutRelations = false): ?CallParticipant
    {
        if ($withoutRelations
            && ! is_null($this->callParticipant)) {
            return $this->callParticipant->withoutRelations();
        }

        return $this->callParticipant;
    }

    /**
     * @inheritDoc
     */
    public function setCallParticipant(CallParticipant $participant = null): self
    {
        if (! is_null($participant)) {
            $this->callParticipant = $participant;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMessage($withoutRelations = false): ?Message
    {
        if ($withoutRelations
            && ! is_null($this->message)) {
            return $this->message->withoutRelations();
        }

        return $this->message;
    }

    /**
     * @inheritDoc
     */
    public function setMessage(Message $message = null): self
    {
        if (! is_null($message)) {
            $this->message = $message;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCall($withoutRelations = false): ?Call
    {
        if ($withoutRelations
            && ! is_null($this->call)) {
            return $this->call->withoutRelations();
        }

        return $this->call;
    }

    /**
     * @inheritDoc
     */
    public function setCall(Call $call = null): self
    {
        if (! is_null($call)) {
            $this->call = $call;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withoutEvents(): self
    {
        $this->shouldFireEvents = false;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withEvents(): self
    {
        $this->shouldFireEvents = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withoutBroadcast(): self
    {
        $this->shouldFireBroadcast = false;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withBroadcast(): self
    {
        $this->shouldFireBroadcast = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withoutDispatches(): self
    {
        $this->shouldFireBroadcast = false;
        $this->shouldFireEvents = false;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withDispatches(): self
    {
        $this->shouldFireBroadcast = true;
        $this->shouldFireEvents = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withoutChains(): self
    {
        $this->shouldExecuteChains = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withChains(): self
    {
        $this->shouldExecuteChains = false;

        return $this;
    }
}
