<?php

declare(strict_types=1);

namespace PixelFederation\OpenSwooleZMQ;

use ZMQ;
use ZMQSocketException;

final class ReadAsyncSocket extends AsyncSocket
{
    public function bind(string $dsn): void
    {
        $this->getSocket()->bind($dsn);
    }

    /**
     * @return array<string>
     * @throws ZMQSocketException
     */
    public function recvmulti(): array
    {
        return $this->getSocket()->recvmulti(ZMQ::MODE_DONTWAIT);
    }

    protected function closeSocket(): void
    {
        /** @var array{bind: array<string>} $endpoints */
        $endpoints = $this->getSocket()->getEndpoints();

        if (empty($endpoints['bind'])) {
            return;
        }

        foreach ($endpoints['bind'] as $endpoint) {
            $this->getSocket()->unbind($endpoint);
        }
    }
}
