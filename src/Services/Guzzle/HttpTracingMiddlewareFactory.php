<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services\Guzzle;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use Psr\Http\Message\RequestInterface;
use Umbrellio\Jaravel\Services\Caller;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Services\Span\SpanTagHelper;

class HttpTracingMiddlewareFactory
{
    public static function create(): callable
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                /** @var TraceContextPropagator $contextPropagator */
                $contextPropagator = App::make(TraceContextPropagator::class);
                /** @var SpanCreator $spanCreator */
                $spanCreator = App::make(SpanCreator::class);

                $span = $spanCreator->create(Caller::call(Config::get('jaravel.guzzle.span_name'), [$request]));

                $headers = [];
                $contextPropagator->inject($headers);

                SpanTagHelper::setTags($span, Caller::call(Config::get('jaravel.guzzle.tags'), [$request]));

                foreach ($headers as $name => $value) {
                    $request = $request->withHeader($name, $value);
                }

                $promise = $handler($request, $options);

                $span->end();

                return $promise;
            };
        };
    }
}
