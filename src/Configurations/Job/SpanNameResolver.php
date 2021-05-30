<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Configurations\Job;

use Illuminate\Contracts\Queue\Job;

class SpanNameResolver
{
    public function __invoke($realJob, ?Job $job = null)
    {
        return 'Job: ' . get_class($realJob);
    }
}
