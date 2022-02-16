#!/usr/bin/env php
<?php

require \dirname(__DIR__) . "/vendor/autoload.php";

use Amp\ByteStream\ResourceOutputStream;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Http\Status;
use Amp\Injector\Application;
use Amp\Injector\Injector;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Promise;
use App\Controller\TestController;
use Monolog\Logger;
use Thgs\Stickman\Dispatch\DispatchCall;
use Thgs\Stickman\Route;
use Thgs\Stickman\RouteCollection;
use Thgs\Stickman\RouteCollector;
use Thgs\Stickman\RouterBuilder;

use function Amp\call;
use function Amp\Injector\any;
use function Amp\Injector\definitions;
use function Amp\Injector\object;

class SomeMiddleware implements Middleware
{
    public function handleRequest(Request $request, RequestHandler $next): Promise
    {
        return call(function () use ($request, $next) {
            $requestTime = microtime(true);
            $request->setHeader('x-request-start', $requestTime);
            $response = yield $next->handleRequest($request);
            $response->setHeader("x-request-time", microtime(true) - $requestTime);
            return $response;
        });
    }
}

class TestHandler implements RequestHandler
{
    #[Route(method: "GET", path: "/something", middleware: [SomeMiddleware::class])]
    public function handleRequest(Request $request): Promise
    {
        return call(function () {
            return new Response(Status::OK, [], "hello world");
        });
    }
}


Amp\Loop::run(static function () {
    $serversCollection = require \dirname(__DIR__) . '/app/config/servers.php';

    // add your container configuration here

    // could we do the same collection from places for this?
    $definitions = definitions()
        ->with(object(TestHandler::class), TestHandler::class)
        ->with(object(SomeMiddleware::class), SomeMiddleware::class)
    ;

    $application = new Application(new Injector(any()), $definitions);
    $container = $application->getContainer();

    $logHandler = new StreamHandler(new ResourceOutputStream(STDOUT));
    $logHandler->setFormatter(new ConsoleFormatter);
    $logger = new Logger('server');
    $logger->pushHandler($logHandler);

    /** @var RouteCollection $routeCollection */
    $routeCollection = (new RouteCollector($logger))->collectFromClass(TestHandler::class)->getRouteCollection();

    $routerBuilder = new RouterBuilder();

    /**
     * @var DispatchCall $dispatchCall
     */
    foreach ($routeCollection->getRoutes() as $dispatchCall => $routes) {
        /** @var Route $route */
        foreach ($routes as $route) {
            $middlewares = $route->getMiddleware();

            $routerBuilder->addRoute(
                $route->getMethod(),
                $route->getPath(),
                $dispatchCall->getClass(),
                ...$middlewares,
            );
        }
    }

    $router = $routerBuilder->buildWith($container);

    // add any custom routes here
    // $router->addRoute(...);


    $httpServer = new HttpServer(
        $serversCollection->collection,
        $router,
        $logger,
        // $options
    );

    # Start server
    yield $httpServer->start();

    # Signals handling
    // Stop the server when SIGINT is received (this is technically optional, but it is best to call Server::stop()).
    Amp\Loop::onSignal(\SIGINT, static function (string $watcherId) use ($httpServer) {
        Amp\Loop::cancel($watcherId);
        yield $httpServer->stop();
    });
});
