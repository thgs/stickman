#!/usr/bin/env php
<?php

require \dirname(__DIR__) . "/vendor/autoload.php";

use Thgs\Stickman\PhpFileContainerConfiguration;
use Thgs\Stickman\Stickman;

Amp\Loop::run(static function () {
    $containerConfig = new PhpFileContainerConfiguration(\dirname(__DIR__) . '/app/config/container.php');
    $handlersCollection = require \dirname(__DIR__) . '/app/config/handlers.php';
    $serversCollection = require \dirname(__DIR__) . '/app/config/servers.php';

    $stickman = new Stickman($containerConfig, $handlersCollection, $serversCollection);

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
