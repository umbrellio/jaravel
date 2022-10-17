<?php declare(strict_types=1);

namespace Umbrellio\Jaravel\Services;

use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;

class TraceIdHeaderRetriever
{
    public function retrieve(array $carrier = [], ?string $keyHeader = TraceContextPropagator::TRACEPARENT): ?string
    {
        if (!$keyHeader) {
            $keyHeader = TraceContextPropagator::TRACEPARENT;
        }

        if (empty($carrier[$keyHeader])) {
            return null;
        }

        if (is_array($carrier[$keyHeader])) {
            return $carrier[$keyHeader][0];
        }

        return $carrier[$keyHeader];
    }
}
