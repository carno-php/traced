<?php
/**
 * UDP Relays
 * User: moyo
 * Date: Jul 23, 2019
 * Time: 10:41
 */

namespace Carno\Traced\Transport;

use Carno\Net\Address;
use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Carno\Tracing\Contracts\Transport;
use Swoole\Client;
use Closure;

class UDPRelays implements Transport
{
    public const MAGIC = '  ZU';

    /**
     * @var Address
     */
    private $endpoint = null;

    /**
     * @var string
     */
    private $identify = 'unknown';

    /**
     * @var Closure
     */
    private $sender = null;

    /**
     * @var resource
     */
    private $socket = null;

    /**
     * @var Client
     */
    private $client = null;

    /**
     * UDPRelays constructor.
     */
    public function __construct()
    {
        if (class_exists('\\Swoole\\Client')) {
            $this->mkSwoole();
        } elseif (function_exists('socket_create')) {
            $this->mkSockets();
        }
    }

    /**
     */
    private function mkSwoole() : void
    {
        $this->client = new Client(SWOOLE_SOCK_UDP, PHP_SAPI === 'cli' ? SWOOLE_SOCK_ASYNC : SWOOLE_SOCK_SYNC);
        $this->sender = function (string $data) {
            $this->client->sendto($this->endpoint->host(), $this->endpoint->port(), $data);
        };
    }

    /**
     */
    private function mkSockets() : void
    {
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        $this->sender = function (string $data) {
            socket_sendto(
                $this->socket,
                $data,
                strlen($data),
                MSG_EOF,
                $this->endpoint->host(),
                $this->endpoint->port()
            );
        };
    }

    /**
     * @param Address $endpoint
     * @param string $identify
     */
    public function connect(Address $endpoint, string $identify = null) : void
    {
        $this->endpoint = $endpoint;
        $this->identify = trim($identify, " /\t\n");
    }

    /**
     * @return Promised
     */
    public function disconnect() : Promised
    {
        $this->socket && socket_close($this->socket);
        $this->client && $this->client = null;
        return Promise::resolved();
    }

    /**
     * @param string $data
     */
    public function loading(string $data) : void
    {
        $this->sender && ($this->sender)($this->packing($data));
    }

    /**
     * @param string $payload
     * @return string
     */
    private function packing(string $payload) : string
    {
        return
            self::MAGIC .
            pack('N', strlen($this->identify)) .
            $this->identify .
            pack('N', strlen($payload)) .
            $payload
        ;
    }

    /**
     */
    public function flushing() : void
    {
    }
}
