<?php

declare(strict_types=1);

namespace Umbrellio\Jaravel\Services\Guzzle;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\Context\Propagation\ArrayAccessGetterSetter;
use Psr\Http\Message\RequestInterface;
use Umbrellio\Jaravel\Services\Caller;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Services\Span\SpanAttributeHelper;

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
                $contextPropagator->inject($headers, ArrayAccessGetterSetter::getInstance());

                SpanAttributeHelper::setAttributes($span, Caller::call(Config::get('jaravel.guzzle.attributes'), [$request]));

                foreach ($headers as $name => $value) {
                    $request = $request->withHeader($name, $value);
                }

                $promise = $handler($request, $options);

                $scope = $span->activate();

                $span->end();
                $scope->detach();

                return $promise;
            };
        };
    }
}
