<?php

namespace Thgs\Stickman;

use Amp\ByteStream\ResourceOutputStream;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Http\Status;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Socket;
use Auryn\Injector;
use Monolog\Logger;

class Stickman
{
    public HttpServer $httpServer;

    public function __construct(
        ConfiguredContainerFactoryInterface|Injector $containerOrFactory,
        HandlersCollection $handlers,
    ) {
        # TLS
        $cert = new Socket\Certificate(__DIR__ . '/../test/server.pem');
        $context = (new Socket\BindContext)
            ->withTlsContext((new Socket\ServerTlsContext)->withDefaultCertificate($cert));

        # Servers
        $servers = [
            Socket\Server::listen("0.0.0.0:1337"),
            Socket\Server::listen("[::]:1337"),
            Socket\Server::listen("0.0.0.0:1338", $context),
            Socket\Server::listen("[::]:1338", $context),
        ];

        # Router
        $router = new Router();
        $router->addRoute('GET', '/', new CallableRequestHandler(function () {
            return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
        }));

        # Logger - for now
        $logHandler = new StreamHandler(new ResourceOutputStream(STDOUT));
        $logHandler->setFormatter(new ConsoleFormatter);
        $logger = new Logger('server');
        $logger->pushHandler($logHandler);

        $injector = $containerOrFactory instanceof Injector 
            ? $containerOrFactory 
            : $containerOrFactory->getContainer();

        $routeCollector = new RouteCollector($router, $logger, $injector);
        foreach ($handlers->collection as $class) {
            $routeCollector->collectFrom($class);
        }

        $this->httpServer = new HttpServer($servers, $routeCollector->getRouter(), $logger);
    }
}