<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services\Span;

use OpenTracing\Span;

class SpanTagHelper
{
    public static function setTags(Span $span, array $tags): void
    {
        foreach ($tags as $tag => $value) {
            $span->setTag($tag, $value);
        }
    }
}
