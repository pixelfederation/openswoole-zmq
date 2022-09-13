<?php

declare(strict_types=1);

namespace PixelFederation\OpenSwooleZMQ;

use RuntimeException;
use ZMQ;
use ZMQContext;
use ZMQSocket;
use ZMQSocketException;

// phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
abstract class AsyncSocket
{
    private ZMQContext $context;

    private ?ZMQSocket $socket = null;

    /**
     * @var resource|null
     */
    private $fd = null;

    private int $type;

    abstract protected function closeSocket(): void;

    public function __construct(int $type)
    {
        $this->type = $type;
        $this->context = new ZMQContext();
    }

    /**
     * @return string|int
     * @throws ZMQSocketException
     */
    public function getSockOpt(int $key)
    {
        /**
         * @psalm-suppress InvalidArgument
         *@phpstan-ignore-next-line
         */
        return $this->getSocket()->getSockOpt($key);
    }

    /**
     * @param mixed $value
     * @throws ZMQSocketException
     */
    public function setSockOpt(int $key, $value): void
    {
        $this->getSocket()->setSockOpt($key, $value);
    }

    public function addReadEventHandlers(callable $readHandler, ?callable $writeHandler = null): void
    {
        /** @psalm-suppress InvalidArgument */
        swoole_event_add($this->fd, $readHandler, $writeHandler, SWOOLE_EVENT_READ); /** @phpstan-ignore-line */
    }

    public function overrideEventHandlersToReadWrite(): void
    {
        /**
         * @psalm-suppress InvalidArgument
         * @phpstan-ignore-next-line
         */
        if (!swoole_event_set($this->fd, null, null, SWOOLE_EVENT_READ | SWOOLE_EVENT_WRITE)) {
            throw new RuntimeException('Unable to override event handler.');
        }
    }

    public function overrideEventHandlersToReadOnly(): void
    {
        /**
         * @psalm-suppress InvalidArgument
         * @phpstan-ignore-next-line
         */
        if (!swoole_event_set($this->fd, null, null, SWOOLE_EVENT_READ)) {
            throw new RuntimeException('Unable to override event handler.');
        }
    }

    public function close(): void
    {
        if ($this->fd !== null) {
            /**
             * @psalm-suppress InvalidArgument
             * @phpstan-ignore-next-line
             */
            if (!swoole_event_del($this->fd)) {
                throw new RuntimeException('Unable to remove socket event.');
            }
            unset($this->fd);
        }

        if ($this->socket === null) {
            return;
        }

        $this->closeSocket();
        unset($this->socket);
    }

    protected function getSocket(): ZMQSocket
    {
        if ($this->socket === null) {
            $this->socket = $this->initSocket();
            /**
             * @psalm-suppress InvalidPropertyAssignmentValue
             * @psalm-suppress InvalidArgument
             * @phpstan-ignore-next-line
             */
            $this->fd = $this->socket->getSockOpt(ZMQ::SOCKOPT_FD);
        }

        return $this->socket;
    }

    private function initSocket(): ZMQSocket
    {
        $socket = $this->context->getSocket($this->type);
        // fix for php process hang in terminal if ZMQ handler is initialised
        // @see http://stackoverflow.com/questions/26884319/php-cli-process-hangs-forever-on-exit
        // Without this line, the script will wait forever after the exit statement
        $socket->setSockOpt(ZMQ::SOCKOPT_LINGER, 1000);

        return $socket;
    }
}
