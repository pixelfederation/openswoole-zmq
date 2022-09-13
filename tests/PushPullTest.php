<?php

declare(strict_types=1);

namespace PixelFederation\OpenSwooleZMQ\Tests;

use PHPUnit\Framework\TestCase;
use PixelFederation\OpenSwooleZMQ\PullConnection;
use PixelFederation\OpenSwooleZMQ\PushConnection;
use Swoole;
use Swoole\Coroutine;
use Swoole\Runtime;

use function Co\defer;

final class PushPullTest extends TestCase
{
    private ?PullConnection $receiverConn = null;

    private ?PushConnection $senderConn = null;

    private const PORT = 5555;

    private const RECEIVER_DSN = 'tcp://0.0.0.0:' . self::PORT;

    private const SENDER_DSN = 'tcp://127.0.0.1:' . self::PORT;

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->receiverConn !== null) {
            $this->receiverConn->close();
        }

        if ($this->senderConn !== null) {
            $this->senderConn->close();
        }
    }

    public function testPushPull(): void
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
            $time = time();

            while (count($results) < $count) {
                if (time() - $time > 3) {
                    break;
                }

                Coroutine::usleep(1000);
            }
        });

        // write context
        $run->add(function() use ($count) {
            defer(function () {
                $this->closeSenderConnection();
            });

            $pub = $this->getSenderConnection();
            $pub->connect();

            for ($i = 1; $i <= $count; $i++) {
                go(function () use ($pub, $i) {
                    $msg = "foo " . $i;
                    $pub->send($msg);
                    // context switch is required to actually send the messages to ZMQ socket
                    Coroutine::usleep(1000);
                });
            }

            go(function () use ($pub, $count) {
                $messages = [];

                for ($i = 1; $i <= $count; $i++) {
                    $messages[] = "foo " . (2 * $i);
                }

                $pub->sendMulti($messages);
                // context switch is required to actually send the messages to ZMQ socket
                Coroutine::usleep(1000);
            });

            // context switch is required to actually send the messages to ZMQ socket
            Coroutine::usleep(1000);
        });

        $run->start();
        self::assertCount(2 * $count, $results);
    }

    private function getReceiverConnection(callable $onMessage): PullConnection
    {
        if ($this->receiverConn === null) {
            $this->receiverConn = new PullConnection(self::RECEIVER_DSN, $onMessage);
        }

        return $this->receiverConn;
    }

    private function getSenderConnection(): PushConnection
    {
        if ($this->senderConn === null) {
            $this->senderConn = new PushConnection(self::SENDER_DSN);
        }

        return $this->senderConn;
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
