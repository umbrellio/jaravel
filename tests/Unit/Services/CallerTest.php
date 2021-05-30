<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Unit\Services;

use Orchestra\Testbench\TestCase;
use Umbrellio\Jaravel\Services\Caller;
use Umbrellio\Jaravel\Services\Exceptions\CallerException;
use Umbrellio\Jaravel\Tests\Utils\InvokableStub;

class CallerTest extends TestCase
{
    public function testCallCallableWithParams(): void
    {
        $callable = fn ($a) => $a;

        $result = Caller::call($callable, [100]);

        $this->assertSame(100, $result);
    }

    public function testCallInvokableWithParams(): void
    {
        $result = Caller::call(InvokableStub::class, [100]);

        $this->assertSame(100, $result);
    }

    public function testExceptionIfUnexpectedType(): void
    {
        $this->expectException(CallerException::class);
        Caller::call(null, []);
    }
}
