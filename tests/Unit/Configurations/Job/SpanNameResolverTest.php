<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Unit\Configurations\Job;

use PHPUnit\Framework\TestCase;
use Umbrellio\Jaravel\Configurations\Job\SpanNameResolver;
use Umbrellio\Jaravel\Tests\Utils\TestJob;

class SpanNameResolverTest extends TestCase
{
    public function testResolve(): void
    {
        $resolver = new SpanNameResolver();

        $job = new TestJob();

        $result = $resolver($job);
        $this->assertSame('Job: Umbrellio\Jaravel\Tests\Utils\TestJob', $result);
    }
}
