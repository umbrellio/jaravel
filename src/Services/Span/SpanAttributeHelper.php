<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services\Span;

use OpenTelemetry\API\Trace\SpanInterface;

class SpanAttributeHelper
{
    public static function setAttributes(SpanInterface $span, array $attributes): void
    {
        foreach ($attributes as $attribute => $value) {
            $span->setAttribute($attribute, $value);
        }
    }
}
