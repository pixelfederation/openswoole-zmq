<?php

declare(strict_types=1);

namespace PixelFederation\OpenSwooleZMQ;

use ZMQ;

final class PullConnection extends ReadConnection
{
    public function __construct(string $dsn, callable $onMessage)
    {
        parent::__construct($dsn, ZMQ::SOCKET_PULL, $onMessage);
    }
}
