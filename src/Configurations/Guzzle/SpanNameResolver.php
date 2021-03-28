<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Configurations\Guzzle;

use Psr\Http\Message\RequestInterface;

class SpanNameResolver
{
    public function __invoke(RequestInterface $request)
    {
        return 'request ' . $request->getUri()->getHost();
    }
}
