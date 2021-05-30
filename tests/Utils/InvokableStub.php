<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Tests\Utils;

class InvokableStub
{
    public function __invoke($a)
    {
        return $a;
    }
}
