<?php

namespace RTippin\Messenger\Services;

use Illuminate\Support\Str;
use RTippin\Messenger\MessengerBots;

class BotMatchingService
{
    /**
     * @param  string  $method
     * @param  string  $trigger
     * @param  string|null  $message
     * @return bool
     */
    public function matches(string $method, string $trigger, ?string $message): bool
    {
        switch ($method) {
            case MessengerBots::MATCH_CONTAINS: return $this->matchContains($trigger, $message);
            case MessengerBots::MATCH_CONTAINS_CASELESS: return $this->matchContains($trigger, $message, true);
            case MessengerBots::MATCH_CONTAINS_ANY: return $this->matchContainsAny($trigger, $message);
            case MessengerBots::MATCH_CONTAINS_ANY_CASELESS: return $this->matchContainsAny($trigger, $message, true);
            case MessengerBots::MATCH_EXACT: return $this->matchExact($trigger, $message);
            case MessengerBots::MATCH_EXACT_CASELESS: return $this->matchExact($trigger, $message, true);
            case MessengerBots::MATCH_STARTS_WITH: return $this->matchStartsWith($trigger, $message);
            case MessengerBots::MATCH_STARTS_WITH_CASELESS: return $this->matchStartsWith($trigger, $message, true);
            default: return false;
        }
    }

    /**
     * The trigger must match the message exactly.
     *
     * @param  string  $trigger
     * @param  string|null  $message
     * @param  bool  $caseless
     * @return bool
     */
    private function matchExact(string $trigger, ?string $message, bool $caseless = false): bool
    {
        $trigger = $caseless ? Str::lower($trigger) : $trigger;
        $message = $this->prepareMessage($message, $caseless);

        return $trigger === $message;
    }

    /**
     * The trigger can be anywhere within a message.
     * Cannot be part of or inside another word.
     *
     * @param  string  $trigger
     * @param  string|null  $message
     * @param  bool  $caseless
     * @return bool
     */
    private function matchContains(string $trigger, ?string $message, bool $caseless = false): bool
    {
        $trigger = $caseless ? Str::lower($trigger) : $trigger;
        $message = $this->prepareMessage($message, $caseless);

        return (bool) preg_match('/(?<=[\s,.:;"\']|^)'.$trigger.'(?=[\s,.:;"\']|$)/', $message);
    }

    /**
     * The trigger can be anywhere within a message,
     * including inside another word.
     *
     * @param  string  $trigger
     * @param  string|null  $message
     * @param  bool  $caseless
     * @return bool
     */
    private function matchContainsAny(string $trigger, ?string $message, bool $caseless = false): bool
    {
        $trigger = $caseless ? Str::lower($trigger) : $trigger;
        $message = $this->prepareMessage($message, $caseless);

        return Str::contains($message, $trigger);
    }

    /**
     * The trigger must be the lead phrase within the message.
     * Cannot be part of or inside another word.
     *
     * @param  string  $trigger
     * @param  string|null  $message
     * @param  bool  $caseless
     * @return bool
     */
    private function matchStartsWith(string $trigger, ?string $message, bool $caseless = false): bool
    {
        $trigger = $caseless ? Str::lower($trigger) : $trigger;
        $message = $this->prepareMessage($message, $caseless);

        return Str::startsWith($message, $trigger)
            && $this->matchContains($trigger, $message, $caseless);
    }

    /**
     * @param  string|null  $string
     * @param  bool  $lower
     * @return string
     */
    private function prepareMessage(?string $string, bool $lower): string
    {
        return trim($lower ? Str::lower($string) : $string);
    }
}
