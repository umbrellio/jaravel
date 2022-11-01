<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services\Span;

use OpenTelemetry\API\Trace\SpanInterface;

class SpanTagHelper
{
    public static function setTags(SpanInterface $span, array $tags): void
    {
        foreach ($tags as $tag => $value) {
            $span->setAttribute($tag, (string) $value);
        }
    }
}
