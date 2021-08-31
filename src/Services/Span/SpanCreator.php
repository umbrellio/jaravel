<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services\Span;

use OpenTracing\Formats;
use OpenTracing\Reference;
use OpenTracing\Span;
use OpenTracing\Tracer;
use Illuminate\Support\Facades\Config;

class SpanCreator
{
    private Tracer $tracer;

    public function __construct(Tracer $tracer)
    {
        $this->tracer = $tracer;
    }

    public function create(string $operationName, ?string $traceIdHeader = null, ?string $referenceType = null): Span
    {
        return $this->tracer->startActiveSpan(
            $operationName,
            $this->detectSpanOptions($traceIdHeader, $referenceType)
        )->getSpan();
    }

    private function detectSpanOptions(?string $traceIdHeader, ?string $referenceType): array
    {
        $baseOptions = [
            'finish_span_on_close' => true,
        ];

        if (!$referenceType) {
            return $baseOptions;
        }

        $spanContext = $this->tracer
            ->extract(Formats\TEXT_MAP, [Config::get('jaravel.trace_id_header', 'x-trace-id') => $traceIdHeader]);

        return array_merge(
            $baseOptions,
            $spanContext ? [
                'references' => new Reference($referenceType, $spanContext),
            ] : []
        );
    }
}
