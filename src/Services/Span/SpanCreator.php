<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services\Span;

use OpenTracing\Formats;
use OpenTracing\Reference;
use OpenTracing\Span;
use OpenTracing\Tracer;

class SpanCreator
{
    private Tracer $tracer;

    public function __construct(Tracer $tracer)
    {
        $this->tracer = $tracer;
    }

    public function create(string $operationName, array $carrier = [], ?string $referenceType = null): Span
    {
        return $this->tracer->startActiveSpan(
            $operationName,
            $this->detectSpanOptions($carrier, $referenceType)
        )->getSpan();
    }

    private function detectSpanOptions(array $carrier, ?string $referenceType): array
    {
        $baseOptions = [
            'finish_span_on_close' => true,
        ];

        if (!$referenceType) {
            return $baseOptions;
        }

        $spanContext = $this->tracer->extract(Formats\TEXT_MAP, $carrier);
        return array_merge(
            $baseOptions,
            $spanContext ? [
                'references' => new Reference($referenceType, $spanContext),
            ] : []
        );
    }
}
