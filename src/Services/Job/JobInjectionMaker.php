<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services\Job;

use OpenTracing\Formats;
use OpenTracing\Tracer;
use Umbrellio\Jaravel\Middleware\JobTracingMiddleware;

class JobInjectionMaker
{
    private Tracer $tracer;

    public function __construct(Tracer $tracer)
    {
        $this->tracer = $tracer;
    }

    public function injectParentSpanToCommand(object $command): object
    {
        $tracingContextField = JobTracingMiddleware::JOB_TRACING_CONTEXT_FIELD;

        if (isset($command->{$tracingContextField})) {
            return $command;
        }

        $span = $this->tracer->getActiveSpan();

        if (!$span) {
            return $command;
        }

        $command->{$tracingContextField} = [];
        $this->tracer->inject($span->getContext(), Formats\TEXT_MAP, $command->{$tracingContextField});

        return $command;
    }
}
