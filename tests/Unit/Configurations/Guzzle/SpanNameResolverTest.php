<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Unit\Configurations\Guzzle;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Umbrellio\Jaravel\Configurations\Guzzle\SpanNameResolver;

class SpanNameResolverTest extends TestCase
{
    public function testResolve(): void
    {
        $resolver = new SpanNameResolver();
        $request = new Request('get', 'https://test.com');

        $result = $resolver($request);
        $this->assertSame('request test.com', $result);
    }
}
