# Jaravel

[![Github Status](https://github.com/umbrellio/jaravel/workflows/CI/badge.svg)](https://github.com/umbrellio/jaravel/actions)
[![Latest Stable Version](https://poser.pugx.org/umbrellio/jaravel/v)](//packagist.org/packages/umbrellio/jaravel)
[![Coverage Status](https://coveralls.io/repos/github/umbrellio/jaravel/badge.svg?branch=master)](https://coveralls.io/github/umbrellio/jaravel?branch=master)

###### Library that allows easy integrate your Laravel application with Jaeger (OpenTracing).
## Installation

Installation can be done with composer
```
composer require umbrellio/jaravel
```
## Usage
Jaravel is able to trace your incoming http requests, console command, 
your guzzle requests to other services and event your queued jobs. 

You can check your configuration in jaravel.php. All configuration is described 
in comment blocks.

You can configure span name or tags for every type of span: 
```php
// config/jaravel.php
...
'http' => [
        'span_name' => fn (Request $request) => 'App: ' . $request->path(),
        'tags' => fn (Request $request, Response $response) => [
            'type' => 'http',
            'request_host' => $request->getHost(),
            'request_path' => $path = $request->path(),
            'request_method' => $request->method(),
            'response_status' => $response->getStatusCode(),
            'error' => !$response->isSuccessful() && !$response->isRedirection(),
        ],
...
    'guzzle' => [
        'span_name' => Umbrellio\Jaravel\Configurations\Guzzle\SpanNameResolver::class,
        'tags' => Umbrellio\Jaravel\Configurations\Guzzle\TagsResolver::class,
    ],
...
```
You can use any callable or fully qualified class name, that points to class 
having `__invoke` method (it will be initialized via Service Container, 
so you can inject anything that you want in constructor). It`s preferred way, if you 
are using `config:cache` Artisan command, because closures can't be serialized. 
Params passed to callable depends on what type of span (http, console, etc).

### Tracing incoming http requests

To enable tracing incoming http requests, you need to add middleware `HttpTracingMiddleware` 
to specific routes or globally, for example. 

Requests can be filtered via 'allow_request' or 'deny_request' callables. 
If 'allow_request' is defined, http request will be traced only if this 
callable will return true. After 'allow_request' Jaravel checks 'deny_request' 
callable, and doesn`t make a trace, if it returns false. If you dont want 
to filter any requests, you can skip this settings.

For example, if you want to trace only requests having '/api' in the path:
```php
// config/jaravel.php
...
'http' => [
        'allow_request' => fn (Request $request) => str_contains($request->path(), '/api'),
...
```

If `'trace_id_header'` is configured, header with trace id will be added to response. 
field.

### Tracing console commands

Enabled by default.

It`s able to filter commands via 'filter_commands', that will not be traced:
```php
// config/jaravel.php
...
'console' => [
        'filter_commands' => ['schedule:run'],
...
```

### Tracing jobs

To start tracing your jobs and even relate it with parent span, you need just 
add `JobTracingMiddleware` to jobs:
```php
<?php
declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Umbrellio\Jaravel\Middleware\JobTracingMiddleware;

class JaravelTestJob implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle()
    {
        ...
    }

    public function middleware()
    {
        return [new JobTracingMiddleware()];
    }
}
```

It recommends to use `InteractsWithQueue` trait, because with this trait you can 
use methods of `Job` instance while tagging span for job:

```php
// config/jaravel.php
...
'job' => [
    'tags' => fn ($realJob, ?Job $job) => [
        'type' => 'job',
        'job_class' => get_class($realJob),
        'job_id' => optional($job)
            ->getJobId(),
        'job_connection_name' => optional($job)
            ->getConnectionName(),
        'job_name' => optional($job)
            ->getName(),
        'job_queue' => optional($job)
            ->getQueue(),
        'job_attempts' => optional($job)
            ->attempts(),
    ],
],
...
```

### Tracing outgoing http requests with Guzzle

To start tracing your Guzzle requests, you need just add middleware to your Guzzle 
client: 
```php
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Umbrellio\Jaravel\HttpTracingMiddlewareFactory;

$stack = HandlerStack::create();
$stack->push(HttpTracingMiddlewareFactory::create());
$client = new Client(['handler' => $stack]);
```

> To add tracing to all your requests you can bind above client to `GuzzleHttp\Client` in 
> your Service Provider.

### Making your own spans

If you need to make your own span, you can follow next example:

```php
use App\Services\MyService;
use OpenTracing\Tracer;
use Umbrellio\Jaravel\Services\Span\SpanCreator;
use Umbrellio\Jaravel\Services\Span\SpanTagHelper;

// You should use dependency injection in your code, it`s just an example 
$spanCreator = app(SpanCreator::class);  
$myService = app(MyService::class);
$tracer = app(Tracer::class);

// First you need to create a span. It will be a child of current active span, if active span exists
$span = $spanCreator->create('Call MyService');

// Do something 
$myService->doSomething();

// Close active scope (span will be finished automatically) and flush tracer.
optional($tracer->getScopeManager()->getActive())->close();
$tracer->flush();
```

If you need to retrieve current trace id, you can use: 
`Umbrellio\Jaravel\Services\Span\ActiveSpanTraceIdRetriever::retrieve()`

## License

Released under MIT License.

## Authors

Created by Vitaliy Lazeev.

## Contributing

- Fork it ( https://github.com/umbrellio/jaravel )
- Create your feature branch (`git checkout -b feature/my-new-feature`)
- Commit your changes (`git commit -am 'Add some feature'`)
- Push to the branch (`git push origin feature/my-new-feature`)
- Create new Pull Request

<a href="https://github.com/umbrellio/">
<img style="float: left;" src="https://umbrellio.github.io/Umbrellio/supported_by_umbrellio.svg" alt="Supported by Umbrellio" width="439" height="72">
</a>
