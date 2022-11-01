<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services\Span;

use OpenTelemetry\SDK\Trace\Span;

class ActiveSpanTraceIdRetriever
{
    public function retrieve(): ?string
    {
        $span = Span::getCurrent();

        if (!$span->getContext()->isValid()) {
            return null;
        }

        return $span->getContext()->getTraceId();
    }
}
