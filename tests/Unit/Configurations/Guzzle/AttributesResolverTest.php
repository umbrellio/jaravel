<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Unit\Configurations\Guzzle;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Umbrellio\Jaravel\Configurations\Guzzle\AttributesResolver;

class AttributesResolverTest extends TestCase
{
    public function testResolve(): void
    {
        $resolver = new AttributesResolver();
        $request = new Request('post', 'https://test.com', [], 'foo=bar');

        $result = $resolver($request);
        $this->assertSame([
            'type' => 'request',
            'uri' => 'https://test.com',
            'method' => 'POST',
            'body' => 'foo=bar',
        ], $result);
    }
}
