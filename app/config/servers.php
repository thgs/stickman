<?php

use Amp\Socket;
use Thgs\Stickman\ServerCollection;

# TLS
$cert = new Socket\Certificate(__DIR__ . '/../test/server.pem');
$context = (new Socket\BindContext)
    ->withTlsContext((new Socket\ServerTlsContext)->withDefaultCertificate($cert));

return new ServerCollection([
    Socket\Server::listen("0.0.0.0:1337"),
    Socket\Server::listen("[::]:1337"),
    Socket\Server::listen("0.0.0.0:1338", $context),
    Socket\Server::listen("[::]:1338", $context),
]);