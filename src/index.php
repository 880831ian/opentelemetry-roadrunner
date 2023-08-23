<?php

include "vendor/autoload.php";

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\LoggerHolder;
use Psr\Log\LogLevel;
use Spiral\RoadRunner;
use Nyholm\Psr7;
use OpenTelemetry\SDK\Trace\TracerProviderFactory;

$worker = RoadRunner\Worker::create();
$psrFactory = new Psr7\Factory\Psr17Factory();
$logger = new Logger('otel-php', [new StreamHandler(STDERR, LogLevel::DEBUG)]);
LoggerHolder::set($logger);

$tracerProvider = (new TracerProviderFactory())->create();
$tracer = $tracerProvider->getTracer('example');

$worker = new RoadRunner\Http\PSR7Worker($worker, $psrFactory, $psrFactory, $psrFactory);

while ($req = $worker->waitRequest()) {
    try {
        $context = TraceContextPropagator::getInstance()->extract($req->getHeaders());
        $rootSpan = $tracer->spanBuilder('root')->setParent($context)->startSpan();
        $scope = $rootSpan->activate();

        try {
            $traceId = $rootSpan->getContext()->getTraceId();
            $spanId = $rootSpan->getContext()->getSpanId();
            $traceIds = [$traceId];
            $spanIds = [$spanId];

            $rsp = new Psr7\Response();
            $rsp = $rsp->withHeader('TraceId', implode(',', $traceIds));
            $rsp = $rsp->withHeader('SpanId', implode(',', $spanIds));
            $rsp->getBody()->write('Hello world!' . "\n" . 'TraceId：' . implode(',', $traceIds) . "\n" . 'SpanId：' . implode(',', $spanIds));
            $worker->respond($rsp);
            $rootSpan->end();
        } finally {
            if ($error = $scope->detach()) {
                $logger->error('Error detaching scope');
            }
        }
    } catch (\Throwable $e) {
        $worker->getWorker()->error((string)$e);
    }
}
