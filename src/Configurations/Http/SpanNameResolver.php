<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Configurations\Http;

use Illuminate\Http\Request;

class SpanNameResolver
{
    public function __invoke(Request $request)
    {
        return 'App: ' . $request->path();
    }
}
