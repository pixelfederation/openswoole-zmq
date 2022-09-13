<?php

declare(strict_types=1);

namespace PixelFederation\OpenSwooleZMQ;

use ZMQ;

final class SubConnection extends ReadConnection
{
    public function __construct(string $dsn, callable $onMessage)
    {
        parent::__construct($dsn, ZMQ::SOCKET_SUB, $onMessage);
    }

    public function subscribe(string $channel): void
    {
        $this->socket->setSockOpt(ZMQ::SOCKOPT_SUBSCRIBE, $channel);
    }

    public function unsubscribe(string $channel): void
    {
        $this->socket->setSockOpt(ZMQ::SOCKOPT_UNSUBSCRIBE, $channel);
    }
}
