<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Contracts;

interface QueueRequestDetector
{
    public function runningQueue(): bool;
}
