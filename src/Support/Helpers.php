<?php

namespace RTippin\Messenger\Support;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Messenger;

class Helpers
{
    /**
     * Generate the URL to a named messenger route.
     *
     * @param  array|string  $name
     * @param  mixed  $parameters
     * @param  bool  $absolute
     * @return string|null
     */
    public static function route(string $name,
                                 $parameters = null,
                                 bool $absolute = false): ?string
    {
        if (app(Router::class)->has($name)) {
            try {
                return app(UrlGenerator::class)->route(
                    $name,
                    $parameters ?: [],
                    Messenger::shouldUseAbsoluteRoutes() ? true : $absolute
                );
            } catch (Exception $e) {
                report($e);
            }
        }

        return null;
    }

    /**
     * @param  Collection  $collection
     * @param  Model|MessengerProvider  $provider
     * @param  string  $morph
     * @return Collection
     */
    public static function forProviderInCollection(Collection $collection,
                                                   $provider,
                                                   string $morph = 'owner'): Collection
    {
        return $collection->where("{$morph}_id", '=', $provider->getKey())
            ->where("{$morph}_type", '=', $provider->getMorphClass());
    }

    /**
     * @param  Carbon|null  $timestamp
     * @return string|null
     */
    public static function precisionTime(?Carbon $timestamp = null): ?string
    {
        return is_null($timestamp)
            ? null
            : $timestamp->format('Y-m-d H:i:s.u');
    }

    /**
     * Helper to set morph key type in migrations.
     *
     * @param  string  $column
     * @param  Blueprint  $table
     * @return void
     */
    public static function schemaMorphType(string $column, Blueprint $table): void
    {
        if (Messenger::shouldUseUuids()) {
            $table->uuidMorphs($column);
        } else {
            $table->numericMorphs($column);
        }
    }
}
