<?php
declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Unit\Configurations\Http;

use Illuminate\Http\Request;
use Umbrellio\Jaravel\Configurations\Http\SpanNameResolver;
use PHPUnit\Framework\TestCase;

class SpanNameResolverTest extends TestCase
{
    public function testResolve(): void
    {
        $resolver = new SpanNameResolver();
        $request = Request::create('https://test.com/api');

        $result = $resolver($request);
        $this->assertSame('App: api', $result);
    }
}
