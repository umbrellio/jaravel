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

    public function create(string $operationName, ?string $traceIdHeader = null): SpanInterface
    {
        return $this
            ->tracer
            ->spanBuilder($operationName)
            ->setParent($this->contextPropagator->extract([TraceContextPropagator::TRACEPARENT => $traceIdHeader]))
            ->startSpan();
    }
}
