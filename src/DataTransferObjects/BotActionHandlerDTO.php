<?php

namespace RTippin\Messenger\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Support\BotActionHandler;

class BotActionHandlerDTO implements Arrayable
{
    /**
     * @var string|BotActionHandler
     */
    public string $class;

    /**
     * @var string
     */
    public string $alias;

    /**
     * @var string
     */
    public string $description;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var bool
     */
    public bool $unique;

    /**
     * @var bool
     */
    public bool $shouldAuthorize;

    /**
     * @var array|null
     */
    public ?array $triggers;

    /**
     * @var string|null
     */
    public ?string $matchMethod;

    /**
     * @param  string  $handler
     *
     * @see BotActionHandler
     */
    public function __construct(string $handler)
    {
        /** @var BotActionHandler $handler */
        $settings = $handler::getSettings();

        $match = $settings['match'] ?? null;

        if ($this->shouldOverwriteTriggers($settings, $match)) {
            $settings['triggers'] = explode('|', BotAction::formatTriggers($settings['triggers']));
        }

        $this->class = $handler;
        $this->alias = $settings['alias'];
        $this->description = $settings['description'];
        $this->name = $settings['name'];
        $this->unique = $settings['unique'] ?? false;
        $this->shouldAuthorize = method_exists($handler, 'authorize');
        $this->triggers = $this->getFinalizedTriggers($settings, $match);
        $this->matchMethod = $match;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'alias' => $this->alias,
            'description' => $this->description,
            'name' => $this->name,
            'unique' => $this->unique,
            'authorize' => $this->shouldAuthorize,
            'triggers' => $this->triggers,
            'match' => $this->matchMethod,
        ];
    }

    /**
     * @param  array  $settings
     * @param  string|null  $match
     * @return bool
     */
    private function shouldOverwriteTriggers(array $settings, ?string $match): bool
    {
        return array_key_exists('triggers', $settings)
            && ! is_null($settings['triggers'])
            && $match !== MessengerBots::MATCH_ANY;
    }

    /**
     * @param  array  $settings
     * @param  string|null  $match
     * @return array|null
     */
    private function getFinalizedTriggers(array $settings, ?string $match): ?array
    {
        if ($match === MessengerBots::MATCH_ANY) {
            return null;
        }

        return $settings['triggers'] ?? null;
    }
}
