<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services\Span;

use OpenTelemetry\SDK\Trace\Span;

class ActiveSpanTraceIdRetriever
{
    public function retrieve(): string
    {
        return Span::getCurrent()->getContext()->getTraceId();
    }
}
