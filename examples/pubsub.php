<?php

declare(strict_types=1);

use PixelFederation\OpenSwooleZMQ\PubConnection;
use PixelFederation\OpenSwooleZMQ\SubConnection;

require_once __DIR__ . '/../vendor/autoload.php';

$sub = new SubConnection(
    'tcp://0.0.0.0:5556',
    static function ($msg): void {
        echo "Received: $msg\n";
    }
);

$sub->bind();
$sub->subscribe('foo');

$pub = new PubConnection('tcp://127.0.0.1:5556');
$pub->connect();

Swoole\Timer::tick(
    1000,
    static function () use ($pub): void {
        static $i = 0;
        $i++;
        $msg = "foo $i";
        echo "Sending: $msg\n";
        $pub->send($msg);
    }
);
