<?php

namespace RTippin\Messenger\Contracts;

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

interface Action
{
    /**
     * Executes the action, allowing variable number of params.
     *
     * @param mixed ...$parameters
     * @return $this
     */
    public function execute(...$parameters);

    /**
     * Disables all events and broadcast globally
     * for any actions during the request cycle.
     */
    public static function disableEvents(): void;

    /**
     * Enables default events and broadcast globally
     * for any actions during the request cycle.
     */
    public static function enableEvents(): void;

    /**
     * Is the current action part of a chain?
     */
    public function isChained(): bool;

    /**
     * Shall we process the chains?
     *
     * @return bool
     */
    public function shouldExecuteChains(): bool;

    /**
     * Should the current action fire its events?
     */
    public function shouldFireEvents(): bool;

    /**
     * @Should the current action fire its broadcast?
     */
    public function shouldFireBroadcast(): bool;

    /**
     * Chain many actions together! Defaults to disabling DB
     * transactions and added events per Action class.
     *
     * @param string|Action $abstractAction
     * @return Action
     * @throws LogicException
     */
    public function chain(string $abstractAction): Action;

    /**
     * Let action know it is being chained.
     *
     * @return $this
     */
    public function continuesChain();

    /**
     * Returns a json resource the action may be holding, if any.
     *
     * @return JsonResource|JsonResponse|mixed
     */
    public function getJsonResource();

    /**
     * @param $resource
     * @return $this
     */
    public function setJsonResource($resource);

    /**
     * @return JsonResponse|mixed|null
     */
    public function getMessageResponse();

    /**
     * @param $messageResponse
     */
    public function setMessageResponse($messageResponse);

    /**
     * Get the raw data response from what the action is working with.
     *
     * @param bool $withoutRelations
     * @return Model|mixed
     */
    public function getData(bool $withoutRelations = false);

    /**
     * @param $data
     * @return $this
     */
    public function setData($data);

    /**
     * Get the thread model the action may be holding.
     *
     * @param bool $withoutRelations
     * @return Thread|null
     */
    public function getThread(bool $withoutRelations = false): ?Thread;

    /**
     * @param ?Thread $thread
     * @return $this
     */
    public function setThread(?Thread $thread = null);

    /**
     * Get the participant model the action may be holding.
     *
     * @param false $withoutRelations
     * @return Participant|null
     */
    public function getParticipant(bool $withoutRelations = false): ?Participant;

    /**
     * @param Participant|null $participant
     * @return $this
     */
    public function setParticipant(?Participant $participant = null): self;

    /**
     * Get the call participant model the action may be holding.
     *
     * @param false $withoutRelations
     * @return CallParticipant|null
     */
    public function getCallParticipant(bool $withoutRelations = false): ?CallParticipant;

    /**
     * @param CallParticipant|null $participant
     * @return $this
     */
    public function setCallParticipant(?CallParticipant $participant = null): self;

    /**
     * Get the message model the action may be holding.
     *
     * @param false $withoutRelations
     * @return Message|null
     */
    public function getMessage(bool $withoutRelations = false): ?Message;

    /**
     * @param Message|null $message
     * @return $this
     */
    public function setMessage(?Message $message = null): self;

    /**
     * Get the call model the action may be holding.
     *
     * @param bool $withoutRelations
     * @return Call|null
     */
    public function getCall(bool $withoutRelations = false): ?Call;

    /**
     * @param ?Call $call
     * @return $this
     */
    public function setCall(?Call $call = null);

    /**
     * Get the bot model the action may be holding.
     *
     * @param bool $withoutRelations
     * @return Bot|null
     */
    public function getBot(bool $withoutRelations = false): ?Bot;

    /**
     * @param ?Bot $bot
     * @return $this
     */
    public function setBot(?Bot $bot = null);

    /**
     * Get the bot action model the action may be holding.
     *
     * @param bool $withoutRelations
     * @return BotAction|null
     */
    public function getBotAction(bool $withoutRelations = false): ?BotAction;

    /**
     * @param BotAction|null $botAction
     * @return mixed
     */
    public function setBotAction(?BotAction $botAction = null);

    /**
     * Disable any event dispatches that the action may hold.
     *
     * @return $this
     */
    public function withoutEvents();

    /**
     * Enable any events dispatches that the action may hold (default).
     *
     * @return $this
     */
    public function withEvents();

    /**
     * Disable any broadcast dispatches that the action may hold.
     *
     * @return $this
     */
    public function withoutBroadcast(): self;

    /**
     * Enable any broadcast dispatches that the action may hold (default).
     *
     * @return $this
     */
    public function withBroadcast(): self;

    /**
     * Disable all event/broadcast dispatches that the action may hold.
     *
     * @return $this
     */
    public function withoutDispatches(): self;

    /**
     * Enable any event/broadcast dispatches that the action may hold.
     *
     * @return $this
     */
    public function withDispatches(): self;

    /**
     * Disable any chained actions from executing in current action.
     *
     * @return $this
     */
    public function withoutChains();

    /**
     * Enable all chained actions, if any (default).
     *
     * @return $this
     */
    public function withChains();
}
