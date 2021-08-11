<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Utils;

use Jaeger\Reporter\ReporterInterface;
use Jaeger\Span;

class SpyReporter implements ReporterInterface
{
    public array $reportedSpans = [];

    public function reportSpan(Span $span)
    {
        $this->reportedSpans = array_merge($this->reportedSpans, [$span]);
    }

    public function close()
    {
    }
}
