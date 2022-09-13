<?php

declare(strict_types=1);

namespace PixelFederation\OpenSwooleZMQ;

use ZMQ;
use ZMQSocketException;

final class WriteAsyncSocket extends AsyncSocket
{
    public function connect(string $dsn): void
    {
        $this->getSocket()->connect($dsn);
    }

    /**
     * @param array<string> $messages
     * @throws ZMQSocketException
     */
    public function sendmulti(array $messages): void
    {
        $this->getSocket()->sendmulti($messages, ZMQ::MODE_DONTWAIT);
    }

    /**
     * @throws ZMQSocketException
     */
    public function send(string $message): void
    {
        $this->getSocket()->send($message, ZMQ::MODE_DONTWAIT);
    }

    protected function closeSocket(): void
    {
        /** @var array{connect: array<string>} $endpoints */
        $endpoints = $this->getSocket()->getEndpoints();

        if (empty($endpoints['connect'])) {
            return;
        }

        foreach ($endpoints['connect'] as $endpoint) {
            $this->getSocket()->disconnect($endpoint);
        }
    }
}
