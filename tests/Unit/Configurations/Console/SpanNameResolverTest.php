<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Unit\Configurations\Console;

use PHPUnit\Framework\TestCase;
use Umbrellio\Jaravel\Configurations\Console\SpanNameResolver;

class SpanNameResolverTest extends TestCase
{
    public function testResolve(): void
    {
        $resolver = new SpanNameResolver();

        $result = $resolver('test_command');
        $this->assertSame('Console: test_command', $result);
    }
}
