<?php

declare(strict_types=1);

use PixelFederation\OpenSwooleZMQ\PushConnection;

require_once __DIR__ . '/../src/OpenSwoole/ZMQ/Connection.php';

$zmq = new PushConnection('tcp://0.0.0.0:5555');
$zmq->connect();

Swoole\Timer::tick(
    1000,
    static function () use ($zmq): void {
        static $i = 0;
        $i++;
        $msg = "hello-$i";
        echo "Sending: $msg\n";
        $zmq->send($msg);
    }
);
