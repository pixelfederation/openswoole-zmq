<?php

declare(strict_types=1);

namespace PixelFederation\OpenSwooleZMQ;

use Closure;
use UnderflowException;
use ZMQ;

abstract class ReadConnection extends Connection
{
    protected ReadAsyncSocket $socket;

    private Closure $onMessage;

    private bool $listening = false;

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function __construct(string $dsn, int $type, callable $onMessage)
    {
        if (!in_array($type, [ZMQ::SOCKET_PULL, ZMQ::SOCKET_SUB], true)) {
            throw new UnderflowException(
                sprintf('Socket type not supported: %d', $type)
            );
        }

        $this->socket = new ReadAsyncSocket($type);
        $this->onMessage = Closure::fromCallable($onMessage);

        parent::__construct($dsn, $this->socket);
    }

    public function bind(): void
    {
        if ($this->listening) {
            throw new ZmqProblem("Already listening.");
        }

        $this->socket->bind($this->dsn);

        /** @psalm-suppress InvalidArgument */
        $this->socket->addReadEventHandlers(
            function (): void {
                $this->handleReadEvent();
            }
        );
        $this->listening = true;
    }

    private function handleReadEvent(): void
    {
        while (!$this->closed) {
            /**
             * @psalm-suppress InvalidArgument
             * @phpstan-ignore-next-line
             * @var int $events
             */
            $events = $this->socket->getSockOpt(ZMQ::SOCKOPT_EVENTS);
            $hasInEvents = $events & ZMQ::POLL_IN;

            if (!$hasInEvents) {
                break;
            }

            $messages = $this->socket->recvmulti();

            foreach ($messages as $message) {
                call_user_func($this->onMessage, $message);
            }
        }
    }
}
