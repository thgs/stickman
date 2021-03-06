#!/usr/bin/env php
<?php

require \dirname(__DIR__) . "/vendor/autoload.php";

use Thgs\Stickman;

Amp\Loop::run(static function () {
    $injector = require \dirname(__DIR__) . '/app/config/injector.php';
    $handlersCollection = require \dirname(__DIR__) . '/app/config/handlers.php';
    $serversCollection = require \dirname(__DIR__) . '/app/config/servers.php';
    $options = require \dirname(__DIR__) . '/app/config/httpServerOptions.php';

    $configuration = new Stickman\Configuration($handlersCollection, $serversCollection, $options);
    $stickman = new Stickman\Stickman($injector, $configuration);

    # Start server
    $server = $stickman->httpServer;
    yield $server->start();

    # Signals handling
    // Stop the server when SIGINT is received (this is technically optional, but it is best to call Server::stop()).
    Amp\Loop::onSignal(\SIGINT, static function (string $watcherId) use ($server) {
        Amp\Loop::cancel($watcherId);
        yield $server->stop();
    });
});
