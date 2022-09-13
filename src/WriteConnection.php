<?php

declare(strict_types=1);

namespace PixelFederation\OpenSwooleZMQ;

use RuntimeException;
use SplQueue;
use UnderflowException;
use ZMQ;
use ZMQSocketException;

abstract class WriteConnection extends Connection
{
    protected WriteAsyncSocket $socket;

    /**
     * @var SplQueue<string|array<string>>
     */
    private SplQueue $messages;

    private bool $connected = false;

    private bool $listening = false;

    protected function __construct(string $dsn, int $type)
    {
        if (!in_array($type, [ZMQ::SOCKET_PUSH, ZMQ::SOCKET_PUB], true)) {
            throw new UnderflowException(
                sprintf('Socket type not supported: %d', $type)
            );
        }

        $this->socket = new WriteAsyncSocket($type);
        /** @psalm-suppress MixedPropertyTypeCoercion */
        $this->messages = new SplQueue();

        parent::__construct($dsn, $this->socket);
    }

    public function connect(): void
    {
        if ($this->connected) {
            throw new ZmqProblem("Already connected.");
        }

        $this->socket->connect($this->dsn);
        $this->socket->addReadEventHandlers(
            function (): void {
                $this->handleReadEvent();
            },
            function (): void {
                $this->handleWriteEvent();
            }
        );
        $this->connected = true;
    }

    public function send(string $message): void
    {
        $this->doSend($message);
    }

    /**
     * @param array<string> $messages
     */
    public function sendMulti(array $messages): void
    {
        $this->doSend($messages);
    }

    /**
     * @param string|array<string> $message
     */
    private function doSend($message): void
    {
        if ($this->closed) {
            throw new RuntimeException('Connection was already closed.');
        }

        $this->messages->enqueue($message);

        if ($this->listening) {
            return;
        }

        $this->listening = true;
        $this->socket->overrideEventHandlersToReadWrite();
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
            $hasOutEvents = $events & ZMQ::POLL_OUT && (int) $this->listening;

            if (!$hasOutEvents) {
                break;
            }

            $this->handleWriteEvent();
        }
    }

    private function handleWriteEvent(): void
    {
        while ($this->messages->count() > 0) {
            $message = $this->messages->dequeue();

            try {
                if (is_array($message)) {
                    $this->socket->sendmulti($message);

                    continue;
                }

                $this->socket->send($message);
            } catch (ZMQSocketException $e) {
                if ($this->onError === null) {
                    return;
                }

                call_user_func($this->onError, $e);
            }
        }

        $this->listening = false;
        $this->socket->overrideEventHandlersToReadOnly();
    }
}
