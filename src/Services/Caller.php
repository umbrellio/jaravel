<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services;

use Illuminate\Support\Facades\App;
use Umbrellio\Jaravel\Services\Exceptions\CallerException;

class Caller
{
    public static function call($callable, array $params)
    {
        if (is_callable($callable)) {
            return $callable(...$params);
        }

        if (is_string($callable)) {
            $callableObject = App::make($callable);

            return $callableObject(...$params);
        }

        throw new CallerException('Unexpected callable parameter');
    }
}
