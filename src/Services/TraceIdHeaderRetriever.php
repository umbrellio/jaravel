<?php declare(strict_types=1);

namespace Umbrellio\Jaravel\Services;

use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;

class TraceIdHeaderRetriever
{
    public function retrieve(array $carrier = []): ?string
    {
        if (empty($carrier[TraceContextPropagator::TRACEPARENT])) {
            return null;
        }

        if (is_array($carrier[TraceContextPropagator::TRACEPARENT])) {
            return $carrier[TraceContextPropagator::TRACEPARENT][0];
        }

        return $carrier[TraceContextPropagator::TRACEPARENT];
    }
}
