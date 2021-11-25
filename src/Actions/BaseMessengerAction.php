<?php

namespace RTippin\Messenger\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;

/**
 * @method BaseMessengerAction execute()
 */
abstract class BaseMessengerAction
{
    /**
     * @var bool
     */
    private static bool $allEventsSilenced = false;

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
     * @var Bot|null
     */
    private ?Bot $bot = null;

    /**
     * @var BotAction|null
     */
    private ?BotAction $botAction = null;

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
     * Disables all events and broadcast globally
     * for any actions during the request cycle.
     */
    public static function disableEvents(): void
    {
        static::$allEventsSilenced = true;
    }

    /**
     * Enables default events and broadcast globally
     * for any actions during the request cycle.
     */
    public static function enableEvents(): void
    {
        static::$allEventsSilenced = false;
    }

    /**
     * Is the current action part of a chain?
     */
    public function isChained(): bool
    {
        return $this->isChained;
    }

    /**
     * Shall we process the chains?
     *
     * @return bool
     */
    public function shouldExecuteChains(): bool
    {
        return $this->shouldExecuteChains;
    }

    /**
     * Should the current action fire its events?
     */
    public function shouldFireEvents(): bool
    {
        return ! static::$allEventsSilenced && $this->shouldFireEvents;
    }

    /**
     * Should the current action fire its broadcast?
     */
    public function shouldFireBroadcast(): bool
    {
        return ! static::$allEventsSilenced && $this->shouldFireBroadcast;
    }

    /**
     * Chain many actions together! Defaults to disabling DB
     * transactions and added events per Action class.
     *
     * @param  string|BaseMessengerAction  $abstractAction
     * @return BaseMessengerAction
     *
     * @throws LogicException
     */
    public function chain(string $abstractAction): BaseMessengerAction
    {
        if (! is_subclass_of($abstractAction, self::class)
            || ! method_exists($abstractAction, 'execute')) {
            throw new LogicException('Invalid chained action.');
        }

        return app($abstractAction)->continuesChain();
    }

    /**
     * Let action know it is being chained.
     *
     * @return $this
     */
    public function continuesChain(): self
    {
        $this->isChained = true;
        $this->shouldExecuteChains = false;

        return $this;
    }

    /**
     * Returns a json resource the action may be holding, if any.
     *
     * @return JsonResource|JsonResponse|mixed
     */
    public function getJsonResource()
    {
        return ! is_null($this->jsonResource)
            ? $this->jsonResource
            : new JsonResponse([]);
    }

    /**
     * @param $resource
     * @return $this
     */
    public function setJsonResource($resource): self
    {
        $this->jsonResource = $resource;

        return $this;
    }

    /**
     * @return JsonResponse|mixed|null
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
     * @param $messageResponse
     * @return $this
     */
    public function setMessageResponse($messageResponse): self
    {
        $this->messageResponse = $messageResponse;

        return $this;
    }

    /**
     * Get the raw data response from what the action is working with.
     *
     * @param  bool  $withoutRelations
     * @return Model|mixed
     */
    public function getData(bool $withoutRelations = false)
    {
        if ($withoutRelations && ! is_null($this->data)) {
            return $this->data->withoutRelations();
        }

        return $this->data;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the thread model the action may be holding.
     *
     * @param  bool  $withoutRelations
     * @return Thread|null
     */
    public function getThread(bool $withoutRelations = false): ?Thread
    {
        if ($withoutRelations && ! is_null($this->thread)) {
            return $this->thread->withoutRelations();
        }

        return $this->thread;
    }

    /**
     * @param  ?Thread  $thread
     * @return $this
     */
    public function setThread(?Thread $thread = null): self
    {
        if (! is_null($thread)) {
            $this->thread = $thread;
        }

        return $this;
    }

    /**
     * Get the participant model the action may be holding.
     *
     * @param  false  $withoutRelations
     * @return Participant|null
     */
    public function getParticipant(bool $withoutRelations = false): ?Participant
    {
        if ($withoutRelations && ! is_null($this->participant)) {
            return $this->participant->withoutRelations();
        }

        return $this->participant;
    }

    /**
     * @param  Participant|null  $participant
     * @return $this
     */
    public function setParticipant(?Participant $participant = null): self
    {
        if (! is_null($participant)) {
            $this->participant = $participant;
        }

        return $this;
    }

    /**
     * Get the call participant model the action may be holding.
     *
     * @param  false  $withoutRelations
     * @return CallParticipant|null
     */
    public function getCallParticipant(bool $withoutRelations = false): ?CallParticipant
    {
        if ($withoutRelations && ! is_null($this->callParticipant)) {
            return $this->callParticipant->withoutRelations();
        }

        return $this->callParticipant;
    }

    /**
     * @param  CallParticipant|null  $participant
     * @return $this
     */
    public function setCallParticipant(?CallParticipant $participant = null): self
    {
        if (! is_null($participant)) {
            $this->callParticipant = $participant;
        }

        return $this;
    }

    /**
     * Get the message model the action may be holding.
     *
     * @param  false  $withoutRelations
     * @return Message|null
     */
    public function getMessage(bool $withoutRelations = false): ?Message
    {
        if ($withoutRelations && ! is_null($this->message)) {
            return $this->message->withoutRelations();
        }

        return $this->message;
    }

    /**
     * @param  Message|null  $message
     * @return $this
     */
    public function setMessage(?Message $message = null): self
    {
        if (! is_null($message)) {
            $this->message = $message;
        }

        return $this;
    }

    /**
     * Get the call model the action may be holding.
     *
     * @param  bool  $withoutRelations
     * @return Call|null
     */
    public function getCall(bool $withoutRelations = false): ?Call
    {
        if ($withoutRelations && ! is_null($this->call)) {
            return $this->call->withoutRelations();
        }

        return $this->call;
    }

    /**
     * @param  ?Call  $call
     * @return $this
     */
    public function setCall(?Call $call = null): self
    {
        if (! is_null($call)) {
            $this->call = $call;
        }

        return $this;
    }

    /**
     * Get the bot model the action may be holding.
     *
     * @param  bool  $withoutRelations
     * @return Bot|null
     */
    public function getBot(bool $withoutRelations = false): ?Bot
    {
        if ($withoutRelations && ! is_null($this->bot)) {
            return $this->bot->withoutRelations();
        }

        return $this->bot;
    }

    /**
     * @param  ?Bot  $bot
     * @return $this
     */
    public function setBot(?Bot $bot = null): self
    {
        if (! is_null($bot)) {
            $this->bot = $bot;
        }

        return $this;
    }

    /**
     * Get the bot action model the action may be holding.
     *
     * @param  bool  $withoutRelations
     * @return BotAction|null
     */
    public function getBotAction(bool $withoutRelations = false): ?BotAction
    {
        if ($withoutRelations && ! is_null($this->bot)) {
            return $this->botAction->withoutRelations();
        }

        return $this->botAction;
    }

    /**
     * @param  BotAction|null  $botAction
     * @return mixed
     */
    public function setBotAction(?BotAction $botAction = null): self
    {
        if (! is_null($botAction)) {
            $this->botAction = $botAction;
        }

        return $this;
    }

    /**
     * Disable any event dispatches that the action may hold.
     *
     * @return $this
     */
    public function withoutEvents(): self
    {
        $this->shouldFireEvents = false;

        return $this;
    }

    /**
     * Enable any events dispatches that the action may hold (default).
     *
     * @return $this
     */
    public function withEvents(): self
    {
        $this->shouldFireEvents = true;

        return $this;
    }

    /**
     * Disable any broadcast dispatches that the action may hold.
     *
     * @return $this
     */
    public function withoutBroadcast(): self
    {
        $this->shouldFireBroadcast = false;

        return $this;
    }

    /**
     * Enable any broadcast dispatches that the action may hold (default).
     *
     * @return $this
     */
    public function withBroadcast(): self
    {
        $this->shouldFireBroadcast = true;

        return $this;
    }

    /**
     * Disable all event/broadcast dispatches that the action may hold.
     *
     * @return $this
     */
    public function withoutDispatches(): self
    {
        $this->shouldFireBroadcast = false;
        $this->shouldFireEvents = false;

        return $this;
    }

    /**
     * Enable any event/broadcast dispatches that the action may hold.
     *
     * @return $this
     */
    public function withDispatches(): self
    {
        $this->shouldFireBroadcast = true;
        $this->shouldFireEvents = true;

        return $this;
    }

    /**
     * Disable any chained actions from executing in current action.
     *
     * @return $this
     */
    public function withoutChains(): self
    {
        $this->shouldExecuteChains = true;

        return $this;
    }

    /**
     * Enable all chained actions, if any (default).
     *
     * @return $this
     */
    public function withChains(): self
    {
        $this->shouldExecuteChains = false;

        return $this;
    }
}
