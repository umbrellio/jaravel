<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services\Job;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Bus\QueueingDispatcher;

class JobWithTracingInjectionDispatcher implements QueueingDispatcher
{
    private Dispatcher $dispatcher;
    private JobInjectionMaker $injectionMaker;

    public function __construct(QueueingDispatcher $dispatcher, JobInjectionMaker $injectionMaker)
    {
        $this->dispatcher = $dispatcher;
        $this->injectionMaker = $injectionMaker;
    }

    public function dispatchNow($command, $handler = null)
    {
        return $this->dispatcher->dispatchNow($this->injectionMaker->injectParentSpanToCommand($command), $handler);
    }

    public function dispatchToQueue($command)
    {
        return $this->dispatcher->dispatchToQueue($this->injectionMaker->injectParentSpanToCommand($command));
    }

    public function dispatch($command)
    {
        return $this->dispatcher->dispatch($this->injectionMaker->injectParentSpanToCommand($command));
    }

    public function dispatchSync($command, $handler = null)
    {
        return $this->dispatcher->dispatchSync($this->injectionMaker->injectParentSpanToCommand($command));
    }

    public function hasCommandHandler($command)
    {
        return $this->dispatcher->hasCommandHandler($command);
    }

    public function getCommandHandler($command)
    {
        return $this->dispatcher->getCommandHandler($command);
    }

    public function pipeThrough(array $pipes)
    {
        return $this->dispatcher->pipeThrough($pipes);
    }

    public function map(array $map)
    {
        return $this->dispatcher->map($map);
    }

    public function findBatch(string $batchId)
    {
        return $this->dispatcher->findBatch($batchId);
    }

    public function batch($jobs)
    {
        return $this->dispatcher->batch($jobs);
    }

    public function chain($jobs)
    {
        return $this->dispatcher->chain($jobs);
    }
}
