<?php declare(strict_types=1);

namespace Umbrellio\Jaravel\Services;

use const Jaeger\TRACE_ID_HEADER;

class TraceIdHeaderRetriever
{
    public function retrieve(array $carrier = []): ?string
    {
        if (empty($carrier[TRACE_ID_HEADER])) {
            return null;
        }

        if (is_array($carrier[TRACE_ID_HEADER])) {
            return $carrier[TRACE_ID_HEADER][0];
        }

        return $carrier[TRACE_ID_HEADER];
    }
}
