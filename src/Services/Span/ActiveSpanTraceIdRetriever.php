<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services\Span;

use Jaeger\Span;
use OpenTracing\Tracer;

class ActiveSpanTraceIdRetriever
{
    private Tracer $tracer;

    public function __construct(Tracer $tracer)
    {
        $this->tracer = $tracer;
    }

    public function retrieve(): ?string
    {
        $activeSpan = $this->tracer->getActiveSpan();
        if (!$activeSpan) {
            return null;
        }

        if (!$activeSpan instanceof Span) {
            return null;
        }

        return $activeSpan->getContext()->getTraceId();
    }
}
