<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services\Job;

use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\Context\Propagation\ArrayAccessGetterSetter;
use Umbrellio\Jaravel\Middleware\JobTracingMiddleware;

class JobInjectionMaker
{
    private TraceContextPropagator $contextPropagator;

    public function __construct(TraceContextPropagator $contextPropagator)
    {
        $this->contextPropagator = $contextPropagator;
    }

    public function injectParentSpanToCommand(object $command): object
    {
        $tracingContextField = JobTracingMiddleware::JOB_TRACING_CONTEXT_FIELD;

        if (isset($command->{$tracingContextField})) {
            return $command;
        }

        $command->{$tracingContextField} = [];
        $this->contextPropagator->inject($command->{$tracingContextField}, ArrayAccessGetterSetter::getInstance());

        return $command;
    }
}
