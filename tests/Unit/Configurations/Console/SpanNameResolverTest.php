<?php
declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Unit\Configurations\Console;

use Umbrellio\Jaravel\Configurations\Console\SpanNameResolver;
use PHPUnit\Framework\TestCase;

class SpanNameResolverTest extends TestCase
{
    public function testResolve(): void
    {
        $resolver = new SpanNameResolver();

        $result = $resolver('test_command');
        $this->assertSame('Console: test_command', $result);
    }
}
