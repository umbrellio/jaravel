<?php declare(strict_types=1);

namespace Umbrellio\Jaravel\Services;

use Illuminate\Support\Facades\Config;

class TraceIdHeaderRetriever
{
    public function retrieve(array $carrier = []): ?string
    {
        $headerName = strtolower(Config::get('jaravel.trace_id_header', 'x-trace-id'));

        if (empty($carrier[$headerName])) {
            return null;
        }

        if (is_array($carrier[$headerName])) {
            return $carrier[$headerName][0];
        }

        return $carrier[$headerName];
    }
}
