<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Configurations\Guzzle;

use Psr\Http\Message\RequestInterface;

class TagsResolver
{
    public function __invoke(RequestInterface $request)
    {
        return [
            'type' => 'request',
            'uri' => (string) $request->getUri(),
            'method' => $request->getMethod(),
            'body' => optional($request->getBody())
                ->getContents(),
        ];
    }
}
