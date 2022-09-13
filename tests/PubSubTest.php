<?php

declare(strict_types=1);

namespace PixelFederation\OpenSwooleZMQ\Tests;

use PHPUnit\Framework\TestCase;
use PixelFederation\OpenSwooleZMQ\PubConnection;
use PixelFederation\OpenSwooleZMQ\SubConnection;
use Swoole;
use Swoole\Coroutine;
use Swoole\Runtime;
use Swoole\Timer;

use function Co\defer;

final class PubSubTest extends TestCase
{
    private ?SubConnection $receiverConn = null;

    private ?PubConnection $senderConn = null;

    private const PORT = 5555;

    private const RECEIVER_DSN = 'tcp://0.0.0.0:' . self::PORT;

    private const SENDER_DSN = 'tcp://127.0.0.1:' . self::PORT;

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->closeConnections();
    }

    public function testPubSub(): void
    {
        Runtime::enableCoroutine();
        $count = 5;
        $results = [];
        $run = new Swoole\Coroutine\Scheduler;

        // read context
        $run->add(function() use ($count, &$results) {
            defer(function () {
                $this->closeReceiverConnection();
            });

            $sub = $this->getReceiverConnection(
                function ($msg) use (&$results) {
                    $results[] = $msg;
                }
            );

            $sub->bind();
            $sub->subscribe('foo');

            $time = time();

            while (count($results) < $count) {
                if (time() - $time > 1) {
                    break;
                }

                Coroutine::usleep(100);
            }
        });

        // write context
        $run->add(function() use ($count, &$results) {
            defer(function () {
                $this->closeSenderConnection();
            });

            $pub = $this->getSenderConnection();
            $pub->connect();

            Timer::tick(
                100,
                function (int $timer) use ($pub, $count) {
                    static $i;
                    $i++;
                    $msg = "foo " . $i;
                    $pub->send($msg);
                    Coroutine::usleep(100);

                    if ($i >= $count) {
                        Timer::clear($timer);
                    }
                }
            );

            $time = time();

            while (count($results) < 5) {
                if (time() - $time > 1) {
                    break;
                }

                Coroutine::usleep(100);
            }
        });

        $run->start();
        self::assertCount($count, $results);
    }

    private function getReceiverConnection(callable $onMessage): SubConnection
    {
        if ($this->receiverConn === null) {
            $this->receiverConn = new SubConnection(self::RECEIVER_DSN, $onMessage);
        }

        return $this->receiverConn;
    }

    private function getSenderConnection(): PubConnection
    {
        if ($this->senderConn === null) {
            $this->senderConn = new PubConnection(self::SENDER_DSN);
        }

        return $this->senderConn;
    }

    private function closeConnections(): void
    {
        if ($this->receiverConn !== null) {
            $this->receiverConn->close();
        }

        if ($this->senderConn !== null) {
            $this->senderConn->close();
        }
    }

    private function closeReceiverConnection(): void
    {
        if ($this->receiverConn !== null) {
            $this->receiverConn->close();
        }
    }

    private function closeSenderConnection(): void
    {
        if ($this->senderConn !== null) {
            $this->senderConn->close();
        }
    }
}
