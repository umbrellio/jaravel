<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Configurations\Http;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;

class SpanNameResolver
{
    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function __invoke(Request $request)
    {
        return 'App: ' . optional($this->router->getRoutes()->match($request))->getName() ?? $request->path();
    }
}
