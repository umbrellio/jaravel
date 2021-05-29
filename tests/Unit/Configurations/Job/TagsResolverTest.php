<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Unit\Configurations\Job;

use Illuminate\Contracts\Queue\Job;
use PHPUnit\Framework\TestCase;
use Umbrellio\Jaravel\Configurations\Job\TagsResolver;
use Umbrellio\Jaravel\Tests\Utils\TestJob;

class TagsResolverTest extends TestCase
{
    public function testResolve(): void
    {
        $resolver = new TagsResolver();

        $realJob = new TestJob();
        $stubJob = $this->createMock(Job::class);

        $stubJob
            ->method('getJobId')
            ->willReturn(100);
        $stubJob
            ->method('getConnectionName')
            ->willReturn('default_connection');
        $stubJob
            ->method('getName')
            ->willReturn('job_name');
        $stubJob
            ->method('getQueue')
            ->willReturn('default_queue');
        $stubJob
            ->method('attempts')
            ->willReturn(1);

        $result = $resolver($realJob, $stubJob);
        $this->assertSame([
            'type' => 'job',
            'job_class' => 'Umbrellio\Jaravel\Tests\Utils\TestJob',
            'job_id' => 100,
            'job_connection_name' => 'default_connection',
            'job_name' => 'job_name',
            'job_queue' => 'default_queue',
            'job_attempts' => 1,
        ], $result);
    }
}
