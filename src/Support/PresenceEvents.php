<?php

namespace RTippin\Messenger\Support;

use Closure;
use InvalidArgumentException;
use RTippin\Messenger\Broadcasting\ClientEvents\Read;
use RTippin\Messenger\Broadcasting\ClientEvents\StopTyping;
use RTippin\Messenger\Broadcasting\ClientEvents\Typing;
use RTippin\Messenger\Broadcasting\MessengerBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;

class PresenceEvents
{
    /**
     * @var string|MessengerBroadcast
     */
    private static string $typingClass = Typing::class;

    /**
     * @var string|MessengerBroadcast
     */
    private static string $stopTypingClass = StopTyping::class;

    /**
     * @var string|MessengerBroadcast
     */
    private static string $readClass = Read::class;

    /**
     * @var Closure|null
     */
    private static ?Closure $typingClosure = null;

    /**
     * @var Closure|null
     */
    private static ?Closure $stopTypingClosure = null;

    /**
     * @var Closure|null
     */
    private static ?Closure $readClosure = null;

    /**
     * @param Closure $typing
     */
    public static function setTypingClosure(Closure $typing): void
    {
        self::$typingClosure = $typing;
    }

    /**
     * @param string $abstractTyping
     * @throws InvalidArgumentException
     */
    public static function setTypingClass(string $abstractTyping): void
    {
        if (! Helpers::checkIsSubclassOf($abstractTyping, MessengerBroadcast::class)) {
            throw new InvalidArgumentException("$abstractTyping must extend our base ".MessengerBroadcast::class);
        }

        self::$typingClass = $abstractTyping;
    }

    /**
     * @return string
     */
    public static function getTypingClass(): string
    {
        return self::$typingClass;
    }

    /**
     * @param Closure $stopTyping
     */
    public static function setStopTypingClosure(Closure $stopTyping): void
    {
        self::$stopTypingClosure = $stopTyping;
    }

    /**
     * @param string $abstractStopTyping
     * @throws InvalidArgumentException
     */
    public static function setStopTypingClass(string $abstractStopTyping): void
    {
        if (! Helpers::checkIsSubclassOf($abstractStopTyping, MessengerBroadcast::class)) {
            throw new InvalidArgumentException("$abstractStopTyping must extend our base ".MessengerBroadcast::class);
        }

        self::$stopTypingClass = $abstractStopTyping;
    }

    /**
     * @return string
     */
    public static function getStopTypingClass(): string
    {
        return self::$stopTypingClass;
    }

    /**
     * @param Closure $read
     */
    public static function setReadClosure(Closure $read): void
    {
        self::$readClosure = $read;
    }

    /**
     * @param string $abstractRead
     * @throws InvalidArgumentException
     */
    public static function setReadClass(string $abstractRead): void
    {
        if (! Helpers::checkIsSubclassOf($abstractRead, MessengerBroadcast::class)) {
            throw new InvalidArgumentException("$abstractRead must extend our base ".MessengerBroadcast::class);
        }

        self::$readClass = $abstractRead;
    }

    /**
     * @return string
     */
    public static function getReadClass(): string
    {
        return self::$readClass;
    }

    /**
     * @param MessengerProvider $provider
     * @return array
     */
    public static function makeTypingEvent(MessengerProvider $provider): array
    {
        if (is_null(self::$typingClosure)) {
            return [
                'provider_id' => $provider->getKey(),
                'provider_alias' => Messenger::findProviderAlias($provider),
                'name' => $provider->getProviderName(),
                'avatar' => $provider->getProviderAvatarRoute(),
            ];
        }

        return (self::$typingClosure)($provider);
    }

    /**
     * @param MessengerProvider $provider
     * @return array
     */
    public static function makeStopTypingEvent(MessengerProvider $provider): array
    {
        if (is_null(self::$stopTypingClosure)) {
            return [
                'provider_id' => $provider->getKey(),
                'provider_alias' => Messenger::findProviderAlias($provider),
                'name' => $provider->getProviderName(),
                'avatar' => $provider->getProviderAvatarRoute(),
            ];
        }

        return (self::$stopTypingClosure)($provider);
    }

    /**
     * @param MessengerProvider $provider
     * @param Message|null $message
     * @return array
     */
    public static function makeReadEvent(MessengerProvider $provider, ?Message $message = null): array
    {
        if (is_null(self::$readClosure)) {
            return [
                'provider_id' => $provider->getKey(),
                'provider_alias' => Messenger::findProviderAlias($provider),
                'name' => $provider->getProviderName(),
                'avatar' => $provider->getProviderAvatarRoute(),
                'message_id' => optional($message)->id,
            ];
        }

        return (self::$readClosure)($provider, $message);
    }

    /**
     * Reset to all defaults.
     */
    public static function reset(): void
    {
        self::$typingClosure = null;
        self::$stopTypingClosure = null;
        self::$readClosure = null;
        self::$typingClass = Typing::class;
        self::$stopTypingClass = StopTyping::class;
        self::$readClass = Read::class;
    }
}
