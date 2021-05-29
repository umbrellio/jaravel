<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Configurations\Job;

use Illuminate\Contracts\Queue\Job;

class TagsResolver
{
    public function __invoke($realJob, ?Job $job = null)
    {
        return [
            'type' => 'job',
            'job_class' => get_class($realJob),
            'job_id' => optional($job)
                ->getJobId(),
            'job_connection_name' => optional($job)
                ->getConnectionName(),
            'job_name' => optional($job)
                ->getName(),
            'job_queue' => optional($job)
                ->getQueue(),
            'job_attempts' => optional($job)
                ->attempts(),
        ];
    }
}
