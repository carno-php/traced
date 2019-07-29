<?php
/**
 * Zipkin implementer
 * User: moyo
 * Date: 23/11/2017
 * Time: 4:22 PM
 */

namespace Carno\Traced\Platform;

use Carno\Config\Config;
use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Carno\Traced\Chips\TransportObserve;
use Carno\Traced\Chips\TransportSync;
use Carno\Traced\Contracts\Observer;
use Carno\Traced\Protocol\AlwaysNone;
use Carno\Traced\Protocol\ZipkinJFV2;
use Carno\Traced\Transport\Blackhole;
use Carno\Traced\Transport\UDPRelays;
use Carno\Traced\Transport\ZipkinHAV2;
use Carno\Traced\Utils\Environment;
use Carno\Tracing\Contracts\Carrier;
use Carno\Tracing\Contracts\Env;
use Carno\Tracing\Contracts\Platform;
use Carno\Tracing\Contracts\Protocol;
use Carno\Tracing\Contracts\Transport;
use Carno\Tracing\Standard\Carriers\HTTP;
use Throwable;

class Zipkin implements Platform, Observer
{
    use TransportSync;
    use TransportObserve;

    /**
     * @var Environment
     */
    private $env = null;

    /**
     * @var Config
     */
    private $conf = null;

    /**
     * @var Carrier
     */
    private $carrier = null;

    /**
     * @var Protocol
     */
    private $protocol = null;

    /**
     * @var Transport
     */
    private $transport = null;

    /**
     * Zipkin constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->conf = $config;

        $this->env = new Environment();
        $this->carrier = new HTTP();
    }

    /**
     * @return Promised
     */
    public function init() : Promised
    {
        $this->syncing(
            $this->conf,
            'tracing.addr',
            [
                'zipkin' => [ZipkinHAV2::class, ZipkinJFV2::class],
                'udp' => [UDPRelays::class, ZipkinJFV2::class],
            ],
            function (Transport $transport = null, Protocol $protocol = null) {
                $this->transport && $this->transport->disconnect();
                $this->transport = $transport;
                $this->protocol = $protocol;
                $this->changed($transport);
            },
            static function (Throwable $e) {
                logger('traced')->warning('Transport initialize failed', [
                    'p' => 'zipkin',
                    'ec' => get_class($e),
                    'em' => $e->getMessage(),
                ]);
            }
        );

        return Promise::resolved();
    }

    /**
     * @return Environment
     */
    public function env() : Env
    {
        return $this->env;
    }

    /**
     * @return bool
     */
    public function joined() : bool
    {
        return $this->carrier && $this->protocol && $this->transport;
    }

    /**
     * @return Promised
     */
    public function leave() : Promised
    {
        return $this->transport ? $this->transport->disconnect() : Promise::resolved();
    }

    /**
     * @return Carrier
     */
    public function carrier() : Carrier
    {
        return $this->carrier;
    }

    /**
     * @return Protocol
     */
    public function serializer() : Protocol
    {
        return $this->protocol ?? new AlwaysNone();
    }

    /**
     * @return Transport
     */
    public function transporter() : Transport
    {
        return $this->transport ?? new Blackhole();
    }
}
