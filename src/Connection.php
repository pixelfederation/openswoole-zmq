<?php

declare(strict_types=1);

namespace PixelFederation\OpenSwooleZMQ;

use Closure;

abstract class Connection
{
    protected string $dsn;

    protected bool $closed = false;

    protected ?Closure $onError = null;

    private AsyncSocket $socket;

    private ?Closure $onClose = null;

    protected function __construct(string $dsn, AsyncSocket $socket)
    {
        $this->dsn = $dsn;
        $this->socket = $socket;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }

        if ($this->onClose !== null) {
            call_user_func($this->onClose);
        }

        $this->socket->close();
        $this->closed = true;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function setCloseHandler(callable $handler): void
    {
        $this->onClose = Closure::fromCallable($handler);
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function setErrorHandler(callable $handler): void
    {
        $this->onError = Closure::fromCallable($handler);
    }
}
