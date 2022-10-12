<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Unit\Configurations\Http;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Umbrellio\Jaravel\Configurations\Http\AttributesResolver;

class AttributesResolverTest extends TestCase
{
    public function testResolve(): void
    {
        $resolver = new AttributesResolver();
        $request = Request::create('https://test.com/api');
        $response = new Response();

        $result = $resolver($request, $response);
        $this->assertSame([
            'type' => 'http',
            'request_host' => 'test.com',
            'request_path' => 'api',
            'request_method' => 'GET',
            'response_status' => 200,
            'error' => false,
        ], $result);
    }
}
