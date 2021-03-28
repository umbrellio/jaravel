<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Utils;

use Jaeger\Jaeger;
use Jaeger\Reporter\Reporter;

class SpyReporter implements Reporter
{
    public array $reportedSpans = [];
    public function report(Jaeger $jaeger)
    {
        $this->reportedSpans = array_merge($this->reportedSpans, $jaeger->spans);
    }

    public function close()
    {
    }
}
