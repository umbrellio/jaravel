<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Unit\Configurations\Console;

use PHPUnit\Framework\TestCase;
use Umbrellio\Jaravel\Configurations\Console\TagsResolver;

class TagsResolverTest extends TestCase
{
    public function testResolve(): void
    {
        $resolver = new TagsResolver();

        $result = $resolver('test_command', 1);
        $this->assertSame([
            'type' => 'console',
            'console_command' => 'test_command',
            'console_exit_code' => 1,
        ], $result);
    }
}
