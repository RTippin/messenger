<?php

namespace RTippin\Messenger\Support;

use Exception;
use Illuminate\Database\Schema\Blueprint;

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
                    $parameters ? $parameters : [],
                    $absolute
                );
            } catch (Exception $e) {
                report($e);
            }
        }

        return null;
    }

    /**
     * Helper to set morph key type in migrations.
     *
     * @param string $column
     * @param Blueprint $table
     * @return void
     */
    public static function SchemaMorphType(string $column, Blueprint $table): void
    {
        if (config('messenger.provider_uuids')) {
            $table->uuidMorphs($column);
        } else {
            $table->numericMorphs($column);
        }
    }
}
