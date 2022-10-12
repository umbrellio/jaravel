<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Configurations\Http;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttributesResolver
{
    public function __invoke(Request $request, Response $response)
    {
        return [
            'type' => 'http',
            'request_host' => $request->getHost(),
            'request_path' => $request->path(),
            'request_method' => $request->method(),
            'response_status' => $response->getStatusCode(),
            'error' => !$response->isSuccessful() && !$response->isRedirection(),
        ];
    }
}
