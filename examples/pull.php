<?php

declare(strict_types=1);

use PixelFederation\OpenSwooleZMQ\PullConnection;

require_once __DIR__ . '/../src/OpenSwoole/ZMQ/Connection.php';

$zmq = new PullConnection(
    'tcp://0.0.0.0:5555',
    static function ($msg): void {
        echo "Received: $msg\n";
    }
);

$zmq->bind();
