#!/usr/bin/env php
<?php

require \dirname(__DIR__) . "/vendor/autoload.php";

use Amp\ByteStream\ResourceOutputStream;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Http\Status;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Socket;
use Monolog\Logger;
use Thgs\Stickman\Controller\TestController;
use Thgs\Stickman\RouteCollector;

// Run this script, then visit http://localhost:1337/ or https://localhost:1338/ in your browser.

Amp\Loop::run(static function () {
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

    # Log
    $logHandler = new StreamHandler(new ResourceOutputStream(STDOUT));
    $logHandler->setFormatter(new ConsoleFormatter);
    $logger = new Logger('server');
    $logger->pushHandler($logHandler);

    # Router
    $router = new Router;
    $router->addRoute('GET', '/', new CallableRequestHandler(function () {
        return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
    }));

    $injector = require \dirname(__DIR__) . '/config/container.php';
    $rc = new RouteCollector($router, $logger, $injector);
    $rc->collectFrom(TestController::class);

    # Start server
    $server = new HttpServer($servers, $rc->getRouter(), $logger);
    yield $server->start();

    # Signals handling

    // Stop the server when SIGINT is received (this is technically optional, but it is best to call Server::stop()).
    Amp\Loop::onSignal(\SIGINT, static function (string $watcherId) use ($server) {
        Amp\Loop::cancel($watcherId);
        yield $server->stop();
    });
});
