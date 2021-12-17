<?php

namespace RTippin\Messenger\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO;
use RTippin\Messenger\DataTransferObjects\ResolvedBotHandlerDTO;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Support\BotActionHandler;

class BotHandlerResolverService
{
    /**
     * @var MessengerBots
     */
    private MessengerBots $bots;

    /**
     * @var BotActionHandler
     */
    private BotActionHandler $handler;

    /**
     * @var BotActionHandlerDTO
     */
    private BotActionHandlerDTO $handlerDTO;

    /**
     * @param  MessengerBots  $bots
     */
    public function __construct(MessengerBots $bots)
    {
        $this->bots = $bots;
    }

    /**
     * Transform the data supplied into a valid ResolvedBotHandlerDTO. We will
     * validate against our base ruleset and any custom rules or overrides on the
     * handler class itself. The handler alias validation can be bypassed if an
     * action's handler class or alias is supplied.
     *
     * @param  array  $data
     * @param  string|null  $handlerOrAlias
     * @return ResolvedBotHandlerDTO
     *
     * @throws ValidationException|BotException
     */
    public function resolve(array $data, ?string $handlerOrAlias = null): ResolvedBotHandlerDTO
    {
        // Validate and initialize the handler / alias
        $this->handler = $this->bots->initializeHandler(
            $handlerOrAlias ?: $this->validateHandlerAlias($data)
        );

        // Set the settings the handler defined
        $this->handlerDTO = $this->bots->getHandler(
            get_class($this->handler)
        );

        // Generate the overrides for the handler.
        $overrides = $this->getHandlerOverrides();

        // Validate against the handler settings, omitting overrides
        $validated = $this->validateHandlerSettings($data, $overrides);

        // Construct the resolved handler DTO from our validated and merged properties
        $resolvedHandlerDTO = $this->generateResolvedHandlerDTO($validated, $overrides);

        // Validate the final formatted triggers to ensure it is
        // not empty if our match method was not "MATCH_ANY"
        $this->validateFormattedTriggers($resolvedHandlerDTO->triggers);

        return $resolvedHandlerDTO;
    }

    /**
     * @param  array  $data
     * @return string
     *
     * @throws ValidationException
     */
    private function validateHandlerAlias(array $data): string
    {
        return Validator::make($data, [
            'handler' => ['required', Rule::in($this->bots->getHandlerAliases())],
        ])->validate()['handler'];
    }

    /**
     * @return array
     */
    private function getHandlerOverrides(): array
    {
        $overrides = [];

        if (! is_null($this->handlerDTO->matchMethod)) {
            $overrides['match'] = $this->handlerDTO->matchMethod;
        }

        if (! is_null($this->handlerDTO->triggers)
            && $this->handlerDTO->matchMethod !== MessengerBots::MATCH_ANY) {
            $overrides['triggers'] = BotAction::formatTriggers($this->handlerDTO->triggers);
        }

        return $overrides;
    }

    /**
     * @param  array  $data
     * @param  array  $overrides
     * @return array
     *
     * @throws ValidationException
     */
    private function validateHandlerSettings(array $data, array $overrides): array
    {
        $mergedRuleset = array_merge([
            'cooldown' => ['required', 'integer', 'between:0,900'],
            'admin_only' => ['required', 'boolean'],
            'enabled' => ['required', 'boolean'],
        ], $this->handler->rules());

        $validator = Validator::make(
            $data,
            $mergedRuleset,
            $this->generateErrorMessages()
        );

        $this->addConditionalHandlerValidations($validator, $overrides);

        return $validator->validate();
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     * @param  array  $overrides
     * @return void
     */
    private function addConditionalHandlerValidations(\Illuminate\Validation\Validator $validator, array $overrides): void
    {
        $validator->sometimes('match', [
            'required',
            'string',
            Rule::in($this->bots->getMatchMethods()),
        ], fn () => ! array_key_exists('match', $overrides));

        $validator->sometimes('triggers', [
            'required',
            'array',
            'min:1',
        ], fn (Fluent $input) => $this->shouldValidateTriggers($input, $overrides));

        $validator->sometimes('triggers.*', [
            'required',
            'string',
        ], fn (Fluent $input) => $this->shouldValidateTriggers($input, $overrides));
    }

    /**
     * @param  Fluent  $input
     * @param  array  $overrides
     * @return bool
     */
    private function shouldValidateTriggers(Fluent $input, array $overrides): bool
    {
        return $this->triggersNotInOverrides($overrides)
            && $this->matchMethodIsNotMatchAny($input)
            && $this->matchMethodOverrideIsNotMatchAny($overrides);
    }

    /**
     * @param  array  $overrides
     * @return bool
     */
    private function triggersNotInOverrides(array $overrides): bool
    {
        return ! array_key_exists('triggers', $overrides);
    }

    /**
     * @param  Fluent  $input
     * @return bool
     */
    private function matchMethodIsNotMatchAny(Fluent $input): bool
    {
        return $input->get('match') !== MessengerBots::MATCH_ANY;
    }

    /**
     * @param  array  $overrides
     * @return bool
     */
    private function matchMethodOverrideIsNotMatchAny(array $overrides): bool
    {
        return ! (array_key_exists('match', $overrides)
            && $overrides['match'] === MessengerBots::MATCH_ANY);
    }

    /**
     * @param  string|null  $triggers
     * @return void
     *
     * @throws ValidationException
     */
    private function validateFormattedTriggers(?string $triggers): void
    {
        if (! is_null($triggers)) {
            Validator::make(['triggers' => $triggers], [
                'triggers' => ['required', 'string'],
            ])->validate();
        }
    }

    /**
     * Merge our error messages with any custom messages defined on the handler.
     *
     * @return array
     */
    private function generateErrorMessages(): array
    {
        return array_merge([
            'triggers.*.required' => 'Trigger field is required.',
            'triggers.*.string' => 'A trigger must be a string.',
        ], $this->handler->errorMessages());
    }

    /**
     * Make the final data array we will pass to create a new BotAction.
     *
     * @param  array  $data
     * @param  array  $overrides
     * @return ResolvedBotHandlerDTO
     */
    private function generateResolvedHandlerDTO(array $data, array $overrides): ResolvedBotHandlerDTO
    {
        return new ResolvedBotHandlerDTO(
            $this->handlerDTO,
            $overrides['match'] ?? $data['match'],
            $data['enabled'],
            $data['admin_only'],
            $data['cooldown'],
            $this->generateTriggers($data, $overrides),
            $this->generatePayload($data)
        );
    }

    /**
     * Strip any non-base rules from the array, then call to the handlers
     * serialize to json encode our payload.
     *
     * @param  array  $data
     * @return string|null
     */
    private function generatePayload(array $data): ?string
    {
        $ruleKeys = [
            'match',
            'cooldown',
            'admin_only',
            'enabled',
            'triggers',
            'triggers.*',
        ];

        $payload = Collection::make($data)
            ->reject(fn ($value, $key) => in_array($key, $ruleKeys))
            ->toArray();

        if (count($payload)) {
            return $this->handler->serializePayload($payload);
        }

        return null;
    }

    /**
     * @param  array  $data
     * @param  array  $overrides
     * @return string|null
     */
    private function generateTriggers(array $data, array $overrides): ?string
    {
        $match = $overrides['match'] ?? $data['match'];

        if ($match === MessengerBots::MATCH_ANY) {
            return null;
        }

        return $overrides['triggers'] ?? BotAction::formatTriggers($data['triggers']);
    }
}
