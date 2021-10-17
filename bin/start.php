#!/usr/bin/env php
<?php

require \dirname(__DIR__) . "/vendor/autoload.php";

use Thgs\Stickman\PhpFileContainerConfiguration;
use Thgs\Stickman\Stickman;

// Run this script, then visit http://localhost:1337/ or https://localhost:1338/ in your browser.

Amp\Loop::run(static function () {
    $containerConfig = new PhpFileContainerConfiguration(\dirname(__DIR__) . '/config/container.php');
    $stickman = new Stickman($containerConfig);

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
