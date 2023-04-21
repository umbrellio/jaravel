<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services;

use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;

class TraceIdHeaderRetriever
{
    public function retrieve(array $carrier, $headerName = TraceContextPropagator::TRACEPARENT): ?string
    {
        if (empty($carrier[$headerName])) {
            return null;
        }

        if (is_array($carrier[$headerName])) {
            return $carrier[$headerName][0];
        }

        return $carrier[$headerName];
    }
}
