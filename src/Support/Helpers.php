<?php

namespace RTippin\Messenger\Support;

use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
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
    public static function Route(string $name,
                                 $parameters = null,
                                 bool $absolute = false): ?string
    {
        if (app('router')->has($name)) {
            try {
                return app('url')->route(
                    $name,
                    $parameters ?: [],
                    $absolute
                );
            } catch (Exception $e) {
                report($e);
            }
        }

        return null;
    }

    /**
     * @param  Carbon|null  $timestamp
     * @return string|null
     */
    public static function PrecisionTime(?Carbon $timestamp = null): ?string
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
    public static function SchemaMorphType(string $column, Blueprint $table): void
    {
        if (Messenger::shouldUseUuids()) {
            $table->uuidMorphs($column);
        } else {
            $table->numericMorphs($column);
        }
    }
}
