<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services\Span;

use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\TracerInterface;

class SpanCreator
{
    private TracerInterface $tracer;
    private TraceContextPropagator $contextPropagator;

    public function __construct(TracerInterface $tracer, TraceContextPropagator $contextPropagator)
    {
        $this->tracer = $tracer;
        $this->contextPropagator = $contextPropagator;
    }

    public function create(string $operationName, ?string $traceIdHeader = null, ?string $traceStateHeader = null): SpanInterface
    {
        $spanBuilder = $this->tracer->spanBuilder($operationName);

        if ($traceIdHeader) {
            $fields = [
                TraceContextPropagator::TRACEPARENT => $traceIdHeader,
                TraceContextPropagator::TRACESTATE => $traceStateHeader ?? null,
            ];
            $context = $this->contextPropagator->extract($fields);
            $spanBuilder->setParent($context);
        }

        return $spanBuilder->startSpan();
    }
}
