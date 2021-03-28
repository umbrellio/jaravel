<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Umbrellio\Jaravel\Services\Caller;

class TracingRequestGuard
{
    public function allowRequest(Request $request): bool
    {
        if (Config::get('jaravel.http.allow_request')) {
            if (!Caller::call(Config::get('jaravel.http.allow_request'), [$request])) {
                return false;
            }
        }

        if (Config::get('jaravel.http.deny_request')) {
            if (Caller::call(Config::get('jaravel.http.deny_request'), [$request])) {
                return false;
            }
        }

        return true;
    }
}
